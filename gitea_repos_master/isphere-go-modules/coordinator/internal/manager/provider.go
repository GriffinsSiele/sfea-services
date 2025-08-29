package manager

import (
	"bytes"
	"context"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"math"
	"net/http"
	"regexp"
	"strconv"
	"strings"
	"time"

	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/clients/graphql"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/clients/keydb"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/clients/shell"
	tcplib "gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/clients/tcp"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/config"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/contract"
	error2 "gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/error"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/hacking"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/model"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/tracing"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/util"
	"github.com/mitchellh/mapstructure"
	"github.com/opentracing/opentracing-go/log"
	"github.com/sirupsen/logrus"
	"github.com/xeipuuv/gojsonschema"
	"gopkg.in/yaml.v2"
)

var (
	matchVariablesBrakesRe = regexp.MustCompile(`(?m){{\s*([^\s}]+)\s*}}`)
	matchVariablesDollarRe = regexp.MustCompile(`(?m)\s+\$(\w+)`)
	matchVariablesDotRe    = regexp.MustCompile(`(?m)\s+\.(\w+)`)
)

type ProviderManager struct {
	cfg           *config.Config
	graphQLClient *graphql.Client
	keyDBClient   *keydb.Client
	tcpClient     *tcplib.Client
	commandClient *shell.Client
}

func NewProviderManager(
	cfg *config.Config,
	graphQLClient *graphql.Client,
	keyDBClient *keydb.Client,
	tcpClient *tcplib.Client,
	commandClient *shell.Client,
) *ProviderManager {
	return &ProviderManager{
		cfg:           cfg,
		graphQLClient: graphQLClient,
		keyDBClient:   keyDBClient,
		tcpClient:     tcpClient,
		commandClient: commandClient,
	}
}

func (t *ProviderManager) Apply(ctx context.Context, checkType *config.CheckType, provider *config.Provider, rawRequestReader io.Reader) (*model.Response, error) {
	keyDB, err := t.keyDBClient.Acquire(ctx)
	if err != nil {
		return nil, fmt.Errorf("failed to acquire keydb connection: %w", err)
	}
	defer keyDB.Release()

	return t.apply(ctx, keyDB, checkType, provider, rawRequestReader)
}

func easyPersistErr(err error) error {
	return fmt.Errorf("failed to easy keydb persist: %w", err)
}

func (t *ProviderManager) apply(ctx context.Context, keyDB *keydb.Conn, checkType *config.CheckType, provider *config.Provider, requestReader io.Reader) (*model.Response, error) {
	tracer, closer := tracing.MustTracerCloser()
	defer tracing.MustClose(closer)

	ctx, span := tracing.StartSpanWithContext(ctx, tracer, "apply the request")
	defer span.Finish()

	request, response := t.processRequest(ctx, checkType, requestReader)
	if response != nil {
		// this response detect that an error
		span.LogFields(log.Object("response", response))
		if response.IsFailed() {
			span.SetTag("error", true)
		}
		if ctx.Value(contract.CacheControlNoStore) == nil && request != nil {
			if err := keyDB.EasyPersist(ctx, request.Key, response, checkType); err != nil {
				logrus.WithContext(ctx).WithError(err).Error("failed to keydb persist")
				return response, easyPersistErr(util.Fail(span, err))
			}
		}
		return response, nil
	}
	span.LogFields(log.Object("request", request))

	for k, v := range request.RawRequest {
		span.SetTag(k, v)
	}

	if request.Timeout != 0 {
		deadline := time.Unix(request.StartTime, 0).Add(time.Duration(request.Timeout) * time.Second)
		if time.Now().After(deadline) {
			return model.NewResponseUsingError(util.Fail(span, errors.New("request timeout")), http.StatusGone), nil
		}
	}

	if response = t.prepare(ctx, keyDB, checkType, request); response != nil {
		// this response detect that response exits in keydb storage
		span.LogFields(log.Object("response", response))
		if response.IsFailed() {
			span.SetTag("error", true)
		}
		if ctx.Value(contract.CacheControlNoStore) == nil {
			if err := keyDB.EasyPersist(ctx, request.Key, response, checkType); err != nil {
				logrus.WithContext(ctx).WithError(err).Error("failed to keydb persist")
				return response, easyPersistErr(util.Fail(span, err))
			}
		}
		return response, nil
	}

	if response = t.exec(ctx, checkType, provider, request); response != nil {
		// this response the final response from check type
		span.LogFields(log.Object("response", response))
		if response.IsFailed() {
			span.SetTag("error", true)
		}
		if ctx.Value(contract.CacheControlNoStore) == nil {
			if err := keyDB.EasyPersist(ctx, request.Key, response, checkType); err != nil {
				logrus.WithContext(ctx).WithError(err).Error("failed to keydb persist")
				return response, easyPersistErr(util.Fail(span, err))
			}
		}

		return response, nil
	}

	return nil, util.Fail(span, errors.New("unknown error, module exec returns nil"))
}

func (t *ProviderManager) processRequest(ctx context.Context, checkType *config.CheckType, requestReader io.Reader) (*model.Request, *model.Response) {
	tracer, closer := tracing.MustTracerCloser()
	defer tracing.MustClose(closer)

	ctx, span := tracing.StartSpanWithContext(ctx, tracer, "process incoming request")
	defer span.Finish()

	requestBytes, err := io.ReadAll(requestReader)
	if err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to read request bytes")
		return nil, model.NewResponseUsingError(util.Fail(span, err), http.StatusBadRequest)
	}
	span.LogFields(log.String("requestBytes", string(requestBytes)))

	var requestData map[string]any
	if err = json.Unmarshal(requestBytes, &requestData); err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to decode request data")
		return nil, model.NewResponseUsingError(util.Fail(span, err), http.StatusBadRequest)
	}
	span.LogFields(log.Object("requestData", requestData))

	if id, ok := requestData["id"]; ok {
		if idStr, ok := id.(string); ok {
			idInt, err := strconv.Atoi(idStr)
			if err != nil {
				logrus.WithContext(ctx).WithError(err).Error("failed to cast id on the request data as int")
				return nil, model.NewResponseUsingError(util.Fail(span, err), http.StatusBadGateway)
			}
			requestData["id"] = idInt
		}
	}

	var request model.Request
	if err = mapstructure.Decode(requestData, &request); err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to decode request data to request model")
		return nil, model.NewResponseUsingError(util.Fail(span, err), http.StatusInternalServerError)
	}
	span.LogFields(log.Object("request", request))

	request.ReInit(requestData)

	if err = t.validate(ctx, checkType, request.RawRequest); err != nil {
		logrus.WithContext(ctx).WithError(err).Error("invalid request for check type")
		return &request, model.NewResponseUsingError(util.Fail(span, err), http.StatusNotAcceptable)
	}

	return &request, nil
}

func (t *ProviderManager) prepare(ctx context.Context, keyDB *keydb.Conn, checkType *config.CheckType, request *model.Request) *model.Response {
	tracer, closer := tracing.MustTracerCloser()
	defer tracing.MustClose(closer)

	ctx, span := tracing.StartSpanWithContext(ctx, tracer, "verify income request for exist in KeyDB")
	defer span.Finish()

	if ctx.Value(contract.CacheControlNoCache) != nil || request.Type == model.RequestTypeCallback {
		return nil
	}

	found, err := keyDB.Exists(ctx, checkType.Upstream.KeyDB.Scope, request.Key)
	if err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to keydb exists")
		return model.NewResponseUsingError(util.Fail(span, err), http.StatusInternalServerError)
	}
	span.LogFields(log.Bool("found", found))

	if !found {
		return nil
	}

	var response model.Response
	if _, err = keyDB.Find(ctx, checkType.Upstream.KeyDB.Scope, request.Key, &response); err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to keydb find")
		return model.NewResponseUsingError(util.Fail(span, err), http.StatusInternalServerError)
	}
	span.LogFields(log.Object("response", response))

	nowTimestamp := time.Unix(response.Timestamp, 0)
	response.Metadata.TTL = &model.ResponseMetadataTTL{
		Age:          util.Ptr(int(math.Round(time.Since(nowTimestamp).Seconds()))),
		LastModified: util.Ptr(nowTimestamp),
	}

	ttl, err := keyDB.TTL(ctx, checkType.Upstream.KeyDB.Scope, request.Key)
	if err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to keydb ttl")
		return model.NewResponseUsingError(util.Fail(span, err), http.StatusInternalServerError)
	}
	span.LogFields(log.Object("ttl", ttl))

	response.Metadata.TTL.ETag = util.Ptr(request.Key)
	response.Metadata.TTL.Expires = util.Ptr(nowTimestamp.Add(util.PtrVal(ttl)))
	span.LogFields(log.Object("response", response))

	return &response
}

func (t *ProviderManager) exec(ctx context.Context, checkType *config.CheckType, provider *config.Provider, request *model.Request) *model.Response {
	tracer, closer := tracing.MustTracerCloser()
	defer tracing.MustClose(closer)

	ctx, span := tracing.StartSpanWithContext(ctx, tracer, "execute check type")
	defer span.Finish()

	if ctx.Value(contract.CacheControlOnlyIfCached) != nil {
		response := model.NewResponseUsingRecords(make(model.Records, 0))
		span.LogFields(log.Object("response", response))
		return response
	}

	rawResponse := make(map[string]any)
	var err error

	switch {
	case checkType.Upstream.GraphQL != nil:
		if response := t.execGraphQL(ctx, checkType, provider, request, &rawResponse); response != nil {
			span.LogFields(log.Object("response", response))
			return response
		}
	case checkType.Upstream.TCP != nil:
		if response := t.execTCP(ctx, checkType, provider, request, &rawResponse); response != nil {
			span.LogFields(log.Object("response", response))
			return response
		}
	case checkType.Upstream.Command != nil:
		if response := t.execCommand(ctx, checkType, provider, request, &rawResponse); response != nil {
			span.LogFields(log.Object("response", response))
			return response
		}
	default:
		return model.NewResponseUsingError(util.Fail(span, errors.New("check type upstream misconfigured")), http.StatusInternalServerError)
	}

	tmpl := util.NewTemplate()
	if _, err = tmpl.Parse(checkType.Mutator.Template.Records); err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to parse mutator template")
		return model.NewResponseUsingError(util.Fail(span, err), http.StatusInternalServerError)
	}
	buf := bytes.NewBuffer([]byte{})
	rawResponse["_input"] = request.RawRequest
	if err = tmpl.Execute(buf, rawResponse); err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to execute mutator template")
		return model.NewResponseUsingError(util.Fail(span, err), http.StatusInternalServerError)
	}
	span.LogFields(log.String("template", buf.String()))

	records := make(model.Records, 0)
	serialized := bytes.TrimSpace(buf.Bytes())
	if len(serialized) > 0 {
		var mapSlices []yaml.MapSlice
		if err = yaml.Unmarshal(serialized, &mapSlices); err != nil {
			logrus.WithContext(ctx).WithError(err).Error("failed to unmarshal records")
			return model.NewResponseUsingError(util.Fail(span, err), http.StatusInternalServerError)
		}
		if records, err = hacking.CastMapSliceAsRecordsYAML(&mapSlices); err != nil {
			logrus.WithContext(ctx).WithError(err).Error("failed to cast map slice as records")
			return model.NewResponseUsingError(util.Fail(span, err), http.StatusInternalServerError)
		}
	}
	span.LogFields(log.Object("records", records))

	if checkType.Produce != nil {
		produceSchemaLoader := gojsonschema.NewGoLoader(checkType.Produce)
		produceDataLoader := gojsonschema.NewGoLoader(records)
		res, err := gojsonschema.Validate(produceSchemaLoader, produceDataLoader)
		if err != nil {
			logrus.WithContext(ctx).WithError(err).Error("failed to validate records")
			return model.NewResponseUsingError(util.Fail(span, err), http.StatusInternalServerError)
		}

		if !res.Valid() {
			var violations []*contract.Violation
			for _, desc := range res.Errors() {
				violations = append(violations, &contract.Violation{
					PropertyPath: desc.Field(),
					Messages:     []string{desc.String()},
				})
			}
			err = &contract.ValidationError{
				Err:        contract.ErrValidation,
				Violations: violations,
			}
			logrus.WithContext(ctx).WithError(err).Error("failed to validate records")
			return model.NewResponseUsingError(util.Fail(span, err), http.StatusNotAcceptable)
		}
	}

	return model.NewResponseUsingRecords(records)
}

func (t *ProviderManager) execGraphQL(ctx context.Context, checkType *config.CheckType, provider *config.Provider, request *model.Request, rawResponse *map[string]any) *model.Response {
	tracer, closer := tracing.MustTracerCloser()
	defer tracing.MustClose(closer)

	ctx, span := tracing.StartSpanWithContext(ctx, tracer, "execute GraphQL request")
	defer span.Finish()

	graphQL, err := t.graphQLClient.Acquire(ctx, provider)
	if err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to acquire graphql connection")
		return model.NewResponseUsingError(util.Fail(span, err), http.StatusInternalServerError)
	}
	defer graphQL.Release()

	var query string
	if q := checkType.Upstream.GraphQL.Query; q != "" {
		query = q
	} else if q := checkType.Upstream.GraphQL.Template.Query; q != "" {
		tmpl := util.NewTemplate()
		if _, err = tmpl.Parse(q); err != nil {
			logrus.WithContext(ctx).WithError(err).Error("failed to parse graphql template")
			return model.NewResponseUsingError(util.Fail(span, err), http.StatusInternalServerError)
		}
		var (
			args = t.addEmptyVariables(q, request.RawRequest)
			buf  = bytes.NewBuffer([]byte{})
		)
		if err = tmpl.Execute(buf, args); err != nil {
			logrus.WithContext(ctx).WithError(err).Error("failed to execute graphql template")
			return model.NewResponseUsingError(util.Fail(span, err), http.StatusInternalServerError)
		}
		query = buf.String()
	} else {
		return model.NewResponseUsingError(util.Fail(span, errors.New("check type graphql upstream misconfigured")), http.StatusInternalServerError)
	}
	span.LogFields(log.String("query", query))

	filteredVariables := t.filterVariables(request.RawRequest, query)
	span.LogFields(log.Object("variables", filteredVariables))
	err = graphQL.Exec(ctx, query, &rawResponse, filteredVariables)
	if err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to execute graphql request")
		statusCode := http.StatusInternalServerError
		if errors.Is(err, error2.UnprocessableEntityError) {
			statusCode = http.StatusUnprocessableEntity
		}
		if strings.Contains(strings.ToLower(err.Error()), "not found") {
			statusCode = http.StatusNotFound
		}
		return model.NewResponseUsingError(util.Fail(span, err), statusCode)
	}
	span.LogFields(log.Object("response", rawResponse))

	return nil
}

func (t *ProviderManager) execTCP(ctx context.Context, checkType *config.CheckType, provider *config.Provider, request *model.Request, rawResponse *map[string]any) *model.Response {
	tracer, closer := tracing.MustTracerCloser()
	defer tracing.MustClose(closer)

	ctx, span := tracing.StartSpanWithContext(ctx, tracer, "execute TCP request")
	defer span.Finish()

	tcp, err := t.tcpClient.Acquire(ctx, provider)
	if err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to acquire tcp connection")
		return model.NewResponseUsingError(util.Fail(span, err), http.StatusInternalServerError)
	}
	defer tcp.Release()

	if checkType.Upstream.TCP.Proxy.Enabled {
		ctx = tcplib.ContextWithProxy(ctx, &checkType.Upstream.TCP.Proxy)
	}
	if r := checkType.Upstream.TCP.Retry; r != nil {
		ctx = tcplib.ContextWithRetry(ctx, r)
	}

	var query string
	if q := checkType.Upstream.TCP.Query; q != "" {
		query = q
	} else if q := checkType.Upstream.TCP.Template.Query; q != "" {
		tmpl := util.NewTemplate()
		if _, err = tmpl.Parse(q); err != nil {
			logrus.WithContext(ctx).WithError(err).Error("failed to parse tcp template")
			return model.NewResponseUsingError(util.Fail(span, err), http.StatusInternalServerError)
		}
		buf := bytes.NewBuffer([]byte{})
		if err = tmpl.Execute(buf, request.RawRequest); err != nil {
			logrus.WithContext(ctx).WithError(err).Error("failed to execute tcp template")
			return model.NewResponseUsingError(util.Fail(span, err), http.StatusInternalServerError)
		}
		query = buf.String()
	}
	span.LogFields(log.String("query", query))

	filteredVariables := t.filterVariables(request.RawRequest, query)
	span.LogFields(log.Object("variables", filteredVariables))
	httpStatusCode, err := tcp.Exec(ctx, query, &rawResponse, filteredVariables)
	span.LogFields(log.Int("status_code", httpStatusCode))
	if err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to execute tcp request")
		return model.NewResponseUsingError(util.Fail(span, err), http.StatusInternalServerError)
	}
	span.LogFields(log.Object("response", rawResponse))

	if httpStatusCode == http.StatusNoContent || httpStatusCode == http.StatusNotFound {
		return nil
	}

	if sp := checkType.Upstream.TCP.SimpleProtocol; sp != nil {
		res := make(model.Records, 0)
		switch httpStatusCode {
		case sp.StatusCodeWhenFound:
			res = append(res, map[string]any{"result_code": "FOUND"})
			response := model.NewResponseUsingRecords(res)
			return response
		case sp.StatusCodeWhenNotFound:
			response := model.NewResponseUsingRecords(res)
			return response
		}
	}

	if sc := checkType.Mutator.StatusCode; sc != nil && sc.NonStandard && rawResponse != nil {
		var scErr error

		if sc.Format != "json" {
			scErr = fmt.Errorf("check type mutator status code misconfigured")
		} else if codeElem, ok := (*rawResponse)[sc.PropertyPath].(any); ok {
			if code, ok := codeElem.(int); ok {
				httpStatusCode = code
			} else if code, ok := codeElem.(float64); ok {
				httpStatusCode = int(code)
			} else {
				scErr = fmt.Errorf("could not cast status code to int")
			}
		} else {
			scErr = fmt.Errorf("no property path found in response body: %s", sc.PropertyPath)
		}

		if httpStatusCode < 200 || httpStatusCode > 399 {
			if detailElem, ok := (*rawResponse)[sc.DetailPropertyPath]; ok {
				if detail, ok := detailElem.(string); ok {
					if scErr != nil {
						span.LogFields(log.String("previous_error", scErr.Error()), log.String("new_error", detail))
					}
					scErr = errors.New(detail)
				}
			}
		}

		if scErr != nil {
			logrus.WithContext(ctx).WithError(scErr).Error("failed to execute tcp request")
			return model.NewResponseUsingError(util.Fail(span, scErr), http.StatusInternalServerError)
		}
	}

	switch httpStatusCode {
	case http.StatusOK:
		return nil
	case http.StatusAccepted:
		return model.NewResponseIncomplete()
	default:
		logrus.WithContext(ctx).Errorf("unexpected response status code: %d", httpStatusCode)
		return model.NewResponseUsingError(util.Fail(span, fmt.Errorf("unexpected response status code: %d", httpStatusCode)), http.StatusBadGateway)
	}
}

func (t *ProviderManager) execCommand(ctx context.Context, checkType *config.CheckType, provider *config.Provider, request *model.Request, rawResponse *map[string]any) *model.Response {
	tracer, closer := tracing.MustTracerCloser()
	defer tracing.MustClose(closer)

	ctx, span := tracing.StartSpanWithContext(ctx, tracer, "execute command")
	defer span.Finish()

	cmd, err := t.commandClient.Acquire(ctx, provider)
	if err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to acquire command connection")
		return model.NewResponseUsingError(util.Fail(span, err), http.StatusInternalServerError)
	}
	defer cmd.Release()

	var query string
	if q := checkType.Upstream.Command.Query; q != "" {
		query = q
	} else if q := checkType.Upstream.Command.Template.Query; q != "" {
		tmpl := util.NewTemplate()
		if _, err = tmpl.Parse(q); err != nil {
			logrus.WithContext(ctx).WithError(err).Error("failed to parse command template")
			return model.NewResponseUsingError(util.Fail(span, err), http.StatusInternalServerError)
		}
		buf := bytes.NewBuffer([]byte{})
		if err = tmpl.Execute(buf, request.RawRequest); err != nil {
			logrus.WithContext(ctx).WithError(err).Error("failed to execute command template")
			return model.NewResponseUsingError(util.Fail(span, err), http.StatusInternalServerError)
		}
		query = buf.String()
	}
	span.LogFields(log.String("query", query))

	filteredVariables := t.filterVariables(request.RawRequest, query)
	span.LogFields(log.Object("variables", filteredVariables))
	err = cmd.Exec(ctx, query, &rawResponse, filteredVariables)
	if err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to execute command")
		return model.NewResponseUsingError(util.Fail(span, err), http.StatusInternalServerError)
	}

	return nil
}

func (t *ProviderManager) filterVariables(in map[string]any, query string) map[string]any {
	var (
		filtered = make(map[string]any, len(in))
		matches  [][]string
	)

	if matchVariablesDollarRe.MatchString(query) {
		matches = matchVariablesDollarRe.FindAllStringSubmatch(query, -1)
	} else if matchVariablesBrakesRe.MatchString(query) {
		matches = matchVariablesBrakesRe.FindAllStringSubmatch(query, -1)
	}

	for _, match := range matches {
		if v, ok := in[match[1]]; ok {
			filtered[match[1]] = v
		} else {
			logrus.WithField("property", match[1]).Warn("required query property was not found")
		}
	}

	return filtered
}

func (t *ProviderManager) addEmptyVariables(query string, in map[string]any) map[string]any {
	var matches [][]string

	switch {
	case matchVariablesDollarRe.MatchString(query):
		matches = matchVariablesDollarRe.FindAllStringSubmatch(query, -1)
	case matchVariablesBrakesRe.MatchString(query):
		matches = matchVariablesBrakesRe.FindAllStringSubmatch(query, -1)
	case matchVariablesDotRe.MatchString(query):
		matches = matchVariablesDotRe.FindAllStringSubmatch(query, -1)
	}

	for _, match := range matches {
		if _, ok := in[match[1]]; !ok {
			in[match[1]] = ""
		}
	}

	return in
}

func (t *ProviderManager) schema(checkType *config.CheckType) map[string]any {
	return map[string]any{
		"allOf":       []any{checkType.Schema},
		"definitions": t.cfg.Definitions,
	}
}

func (t *ProviderManager) validate(ctx context.Context, checkType *config.CheckType, consumeData map[string]any) error {
	consumeSchemaLoader := gojsonschema.NewGoLoader(t.schema(checkType))
	consumeDataLoader := gojsonschema.NewGoLoader(consumeData)
	res, err := gojsonschema.Validate(consumeSchemaLoader, consumeDataLoader)
	if err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to validate consume schema")
		return fmt.Errorf("validate schema: %w", err)
	}

	if !res.Valid() {
		var violations []*contract.Violation
		for _, desc := range res.Errors() {
			violations = append(violations, &contract.Violation{
				PropertyPath: desc.Field(),
				Messages:     []string{desc.String()},
			})
		}
		return &contract.ValidationError{Violations: violations, Err: contract.ErrValidation}
	}

	return nil
}
