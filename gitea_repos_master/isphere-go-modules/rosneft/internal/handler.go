package internal

import (
	"encoding/json"
	"fmt"
	"log/slog"
	"net/http"
	"net/url"
	"os"
	"strings"
	"time"

	"github.com/gin-gonic/gin"
	"github.com/google/uuid"
	"github.com/pkg/errors"
	"github.com/soulkoden/logrusotel/pkg"
	"golang.org/x/net/context"
)

const tracerName string = "rosneft"
const tracerQueueSize int = 10

type Handler struct {
	client *http.Client
}

func NewHandler(client *http.Client) *Handler {
	return &Handler{
		client: client,
	}
}

func (t *Handler) ServeHTTP(c *gin.Context) {
	tracer, closer := pkg.MustTracerCloser(tracerName, tracerQueueSize)
	defer pkg.MustClose(closer)

	ctx, span := pkg.StartSpanWithContext(c, tracer, "receive request")
	defer span.Finish()

	c.Request = c.Request.WithContext(ctx)

	t.checkRequestIDExistsAndCreateItIfIsNotSet(ctx, c)

	input, err := t.extractInputWithContext(ctx, c)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": pkg.SpanError(span, err).Error()})
		return
	}
	span.LogKV("input", input)

	formData, err := t.createFormDataWithInput(ctx, input)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": pkg.SpanError(span, err).Error()})
		return
	}
	span.LogKV("formData", formData)

	response, err := t.tryToSendFormDataViaProxyMultipleTimes(ctx, c, formData)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": pkg.SpanError(span, err).Error()})
		return
	}
	span.LogKV("response", response)

	if response.Code != 0 {
		if response.Code >= 500 {
			errorData := gin.H{
				"errors": []any{response.Message},
			}
			if response.Code > 599 {
				errorData["response_status_code"] = response.Code
				response.Code = http.StatusInternalServerError
			}
			c.JSON(response.Code, errorData)
			return
		}

		c.JSON(http.StatusOK, gin.H{"data": []any{}})
		return
	}

	c.JSON(http.StatusOK, gin.H{"data": []any{response}})
}

func (t *Handler) checkRequestIDExistsAndCreateItIfIsNotSet(ctx context.Context, c *gin.Context) {
	tracer, closer := pkg.MustTracerCloser(tracerName, tracerQueueSize)
	defer pkg.MustClose(closer)

	ctx, span := pkg.StartSpanWithContext(ctx, tracer, "check request id")
	defer span.Finish()

	var requestID string
	if requestID = c.Request.Header.Get("X-Request-ID"); requestID == "" {
		requestID = uuid.NewString()
		c.Request.Header.Set("X-Request-ID", requestID)
	}

	span.LogKV("request_id", requestID)
}

func (t *Handler) extractInputWithContext(ctx context.Context, c *gin.Context) (*Input, error) {
	input := new(Input)
	if err := c.ShouldBindJSON(input); err != nil {
		return nil, errors.Wrap(err, "failed to bind input")
	}
	return input, nil
}

func (t *Handler) createFormDataWithInput(ctx context.Context, input *Input) (url.Values, error) {
	formData := url.Values{}
	switch {
	case input.Phone != "":
		formData.Set("phone", input.Phone)
	default:
		return nil, errors.New("no email or phone provided")
	}
	return formData, nil
}

func (t *Handler) tryToSendFormDataViaProxyMultipleTimes(ctx context.Context, c *gin.Context, formData url.Values) (*RosneftResponse, error) {
	tracer, closer := pkg.MustTracerCloser(tracerName, tracerQueueSize)
	defer pkg.MustClose(closer)

	ctx, span := pkg.StartSpanWithContext(ctx, tracer, "try to send form data via proxy multiple times")
	defer span.Finish()

	for i := 0; i < 10; i++ {
		response, err := t.tryToSendFormDataViaProxy(ctx, c, formData)
		if err != nil {
			slog.With("error", err).WarnContext(ctx, "failed to send form data via proxy")
			continue
		}

		return response, nil
	}

	return nil, pkg.SpanError(span, errors.New("failed to send form data via proxy"))
}

func (t *Handler) tryToSendFormDataViaProxy(ctx context.Context, c *gin.Context, formData url.Values) (*RosneftResponse, error) {
	tracer, closer := pkg.MustTracerCloser(tracerName, tracerQueueSize)
	defer pkg.MustClose(closer)

	ctx, span := pkg.StartSpanWithContext(ctx, tracer, "try to send form data via proxy")
	defer span.Finish()

	ctx, cancel := context.WithTimeout(ctx, 5*time.Second)
	defer cancel()

	req, err := http.NewRequestWithContext(
		ctx, http.MethodPost, os.Getenv("ROSNEFT_ENDPOINT"),
		strings.NewReader(formData.Encode()),
	)
	if err != nil {
		return nil, pkg.SpanError(span, errors.Wrap(err, "failed to create request"))
	}

	req.Header.Set("Connection", "Keep-Alive")
	req.Header.Set("X-Build-Number", "2156")
	req.Header.Set("X-Device-ID", uuid.NewString())
	req.Header.Set("X-Device-Type", "android")
	req.Header.Set("X-Region-Code", "78")
	req.Header.Set("Content-Type", "application/x-www-form-urlencoded")
	req.Header.Set("User-Agent", "okhttp/5.0.0-alpha.9")
	req.Header.Set("X-Sphere-Proxy-Spec-Group-Id", "5")
	//req.Header.Set("X-Sphere-Proxy-Spec-Strategy", "pass")

	span.LogKV("request", req)

	resp, err := t.client.Do(req)
	if err != nil {
		return nil, pkg.SpanError(span, errors.Wrap(err, "failed to send request"))
	}
	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	span.LogKV("status_code", resp.StatusCode)

	if resp.StatusCode != http.StatusOK {
		return nil, pkg.SpanError(span, fmt.Errorf("unexpected response status code: %d", resp.StatusCode))
	}

	var response RosneftResponse
	if err = json.NewDecoder(resp.Body).Decode(&response); err != nil {
		return nil, pkg.SpanError(span, errors.Wrap(err, "failed to decode response body"))
	}

	span.LogKV("response", resp)

	return &response, nil
}
