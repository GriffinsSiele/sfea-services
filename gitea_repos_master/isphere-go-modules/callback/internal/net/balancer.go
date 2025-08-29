package net

import (
	"bytes"
	"context"
	"errors"
	"fmt"
	"io"
	"net/http"
	"net/url"
	"strings"

	"git.i-sphere.ru/isphere-go-modules/callback/internal/client"
	"github.com/charmbracelet/log"
	"github.com/gin-gonic/gin"
	jsoniter "github.com/json-iterator/go"
	"github.com/rabbitmq/amqp091-go"
	"github.com/xeipuuv/gojsonschema"
	"gopkg.in/yaml.v2"

	"git.i-sphere.ru/isphere-go-modules/callback/internal/config"
	"git.i-sphere.ru/isphere-go-modules/callback/internal/connection"
	"git.i-sphere.ru/isphere-go-modules/callback/internal/util"
)

var json = jsoniter.ConfigCompatibleWithStandardLibrary

type Balancer struct {
	amqp *connection.AMQP
	cfg  *config.Config
}

func NewBalancer(
	amqp *connection.AMQP,
	cfg *config.Config,
) (*Balancer, error) {
	return &Balancer{
		amqp: amqp,
		cfg:  cfg,
	}, nil
}

func (t *Balancer) Apply(c *gin.Context) error {
	tracer, closer := client.MustTracerCloser()
	defer client.MustClose(closer)

	c, span := client.StartSpanWithGinContext(c, tracer, "apply balancer")
	defer span.Finish()

	span.LogEvent("finding rule")
	rule, err := t.findRule(c)
	if err != nil {
		return client.Fail(span, fmt.Errorf("cannot find rule: %w", err))
	}
	span.SetTag("rule", rule.Name)

	span.LogEvent("parsing request")
	req, err := t.parseRequest(c, rule)
	if err != nil {
		return client.Fail(span, fmt.Errorf("failed to parse request: %w", err))
	}
	span.LogKV("request", req)

	span.LogEvent("validating request")
	if err = t.validate(rule, req); err != nil {
		return client.Fail(span, fmt.Errorf("validation error: %w", err))
	}

	span.LogEvent("making payload")
	key, res, err := t.makePayloadWithKeys(rule, req)
	if err != nil {
		return client.Fail(span, fmt.Errorf("cannot make payload: %w", err))
	}
	span.SetTag("key", key)
	span.LogKV("payload", res)

	span.LogEvent("publishing payload")
	if err = t.publish(c, rule, key, res); err != nil {
		return client.Fail(span, fmt.Errorf("publishing fail: %w", err))
	}

	return nil
}

func (t *Balancer) findRule(c *gin.Context) (*config.Rule, error) {
	var rule *config.Rule

	for name, r := range t.cfg.Rules {
		if expr := r.GetPatternExpr(); expr != nil && expr.MatchString(c.Request.RequestURI) {
			rule = r
			r.Name = name

			break
		}
	}

	if rule == nil || !rule.Enabled {
		return nil, errors.New("no rule found for request criteria")
	}

	return rule, nil
}

func (t *Balancer) parseRequest(c *gin.Context, rule *config.Rule) (map[string]any, error) {
	reqBody, err := io.ReadAll(c.Request.Body)
	if err != nil {
		return nil, fmt.Errorf("failed to read request body: %w", err)
	}

	c.Request.Body = io.NopCloser(bytes.NewReader(reqBody))

	//goland:noinspection GoUnhandledErrorResult
	defer c.Request.Body.Close()

	var req map[string]any

	switch rule.Mutator.Marshaller {
	case config.MutatorMarshallerJSON:
		if err = json.Unmarshal(reqBody, &req); err != nil {
			return nil, fmt.Errorf("failed to unmarshal request body: %w", err)
		}

	case config.MutatorMarshallerForm:
		req = make(map[string]any)
		rawData, err := url.ParseQuery(string(reqBody))
		if err != nil {
			return nil, fmt.Errorf("failed to unmarshal request body: %w", err)
		}

		for k, values := range rawData {
			for _, value := range values {
				if unescaped, err := url.QueryUnescape(value); err != nil {
					log.With("err", err).Errorf("failed to unescape value: %v", value)

					req[k] = value
				} else {
					req[k] = unescaped
				}
			}
		}

	case config.MutatorMarshallerNone:

	default:
		return nil, errors.New("cannot unmarshal request, no mutator marshaller provided")
	}

	return req, nil
}

func (t *Balancer) validate(rule *config.Rule, req any) error {
	if rule.Schema == nil {
		return nil
	}

	var (
		ls = gojsonschema.NewGoLoader(rule.Schema)
		ld = gojsonschema.NewGoLoader(req)
	)

	r, err := gojsonschema.Validate(ls, ld)
	if err != nil {
		return fmt.Errorf("failed to validate request: %w", err)
	}

	if !r.Valid() {
		var violations []string
		for _, desc := range r.Errors() {
			violations = append(violations, desc.String())
		}

		return fmt.Errorf("validation error: %v", violations)
	}

	return nil
}

func (t *Balancer) makePayloadWithKeys(rule *config.Rule, req map[string]any) (string, map[string]any, error) {
	var (
		key     string
		payload []byte
		err     error
	)

	if rule.Mutator.Key.Template != "" {
		tmpl := util.NewTemplate()
		if _, err = tmpl.Parse(rule.Mutator.Key.Template); err != nil {
			return "", nil, fmt.Errorf("failed to parse mutator key template: %w", err)
		}

		buf := bytes.NewBuffer([]byte{})
		if err = tmpl.Execute(buf, req); err != nil {
			return "", nil, fmt.Errorf("failed to render mutator key template: %w", err)
		}

		key = strings.TrimSpace(buf.String())
		if key == "" {
			return "", nil, errors.New("failed to bind callback to internal service but a valid key is not set")
		}
	}

	if rule.Mutator.Template != "" {
		tmpl := util.NewTemplate()
		if _, err = tmpl.Parse(rule.Mutator.Template); err != nil {
			return "", nil, fmt.Errorf("failed to parse mutator template: %w", err)
		}

		buf := bytes.NewBuffer([]byte{})
		if err = tmpl.Execute(buf, req); err != nil {
			return "", nil, fmt.Errorf("failed to render mutator template: %w", err)
		}

		payload = bytes.TrimSpace(buf.Bytes())
		if len(payload) == 0 {
			return "", nil, errors.New("failed to bind callback to internal service but a valid payload is not set")
		}
	}

	var res map[string]any
	if err = yaml.Unmarshal(payload, &res); err != nil {
		return "", nil, fmt.Errorf("cannot parse mutator result: %w", err)
	}

	return key, res, nil
}

func (t *Balancer) publish(c *gin.Context, rule *config.Rule, key string, res map[string]any) error {
	if rule.Downstream.RabbitMQ.Enabled {
		if err := t.publishRabbitMQ(c, rule, key, res); err != nil {
			return fmt.Errorf("failed to publish to rabbitmq: %w", err)
		}
	}

	if rule.Downstream.Proxy.Enabled {
		if err := t.publishProxy(c, rule, key, res); err != nil {
			return fmt.Errorf("failed to publish to proxy: %w", err)
		}
	}

	return nil
}

func (t *Balancer) publishRabbitMQ(ctx context.Context, rule *config.Rule, key string, res map[string]any) error {
	conn, err := t.amqp.Acquire()
	if err != nil {
		return fmt.Errorf("failed to acquire amqp connection: %w", err)
	}

	serialized, err := json.Marshal(res)
	if err != nil {
		return fmt.Errorf("failed to re-serialize response: %w", err)
	}

	publishing := amqp091.Publishing{
		Headers: amqp091.Table{
			"content-type": "application/json",
			"x-message-id": key,
		},
		Body: serialized,
	}

	if err = conn.PublishWithContext(ctx, rule.Downstream.RabbitMQ.Scope, "", false, false, publishing); err != nil {
		return fmt.Errorf("failed to publish callback response to amqp: %w", err)
	}

	return nil
}

func (t *Balancer) publishProxy(c *gin.Context, rule *config.Rule, _ string, _ map[string]any) error {
	// select method
	requestMethod := util.OneOf(c.Request.Method, rule.Downstream.Proxy.RewriteMethod)

	// select host and request path
	requestURL, err := url.Parse(rule.Downstream.Proxy.Host)
	if err != nil {
		return fmt.Errorf("failed to parse proxy url: %w", err)
	}
	if pathOverride := rule.Downstream.Proxy.RewritePath; pathOverride != "" {
		requestURL.Path = pathOverride
	} else {
		originalURL, err := url.Parse(c.Request.RequestURI)
		if err != nil {
			return fmt.Errorf("failed to parse original url: %w", err)
		}
		requestURL.Path = originalURL.Path
	}
	requestURL.RawQuery = c.Request.URL.RawQuery
	requestURL.RawFragment = c.Request.URL.RawFragment

	// make the proxy request
	proxyRequest, err := http.NewRequestWithContext(c, requestMethod, requestURL.String(), c.Request.Body)
	if err != nil {
		return fmt.Errorf("failed to create proxy request: %w", err)
	}

	// copy headers
	for k, v := range c.Request.Header {
		proxyRequest.Header[k] = v
	}

	logPrefix := "net.Balancer [" + c.Request.Header.Get("X-Request-ID") + "]"
	l, err := util.LogRequest(c, proxyRequest)
	if err != nil {
		return fmt.Errorf("failed to log request: %w", err)
	}
	l.WithPrefix(logPrefix).Info("send request to proxy")

	// And run
	proxyClient := http.Client{}
	proxyResponse, err := proxyClient.Do(proxyRequest)
	if err != nil {
		return fmt.Errorf("failed to proxy request: %w", err)
	}

	l, err = util.LogResponse(c, proxyResponse)
	if err != nil {
		return fmt.Errorf("failed to log response: %w", err)
	}
	l.WithPrefix(logPrefix).Info("received response from proxy")

	if expectedStatusCode := rule.Downstream.Proxy.ExpectedStatusCode; expectedStatusCode > 0 && expectedStatusCode != proxyResponse.StatusCode {
		return fmt.Errorf("expected status code %d but got %d", expectedStatusCode, proxyResponse.StatusCode)
	}

	return nil
}
