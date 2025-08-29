package internal

import (
	"encoding/json"
	"fmt"
	"net/http"
	"net/url"
	"os"
	"strings"
	"time"

	"github.com/gin-gonic/gin"
	"github.com/google/uuid"
	"github.com/sirupsen/logrus"
	"go.opentelemetry.io/otel/attribute"
	"go.opentelemetry.io/otel/trace"
	"golang.org/x/net/context"
)

type Handler struct {
	client    *http.Client
	tracer    trace.Tracer
	ja3Loader *Ja3Loader
}

func NewHandler(client *http.Client, tracer trace.Tracer, ja3Loader *Ja3Loader) *Handler {
	return &Handler{
		client:    client,
		tracer:    tracer,
		ja3Loader: ja3Loader,
	}
}

func (t *Handler) ServeHTTP(c *gin.Context) {
	ctx, span := t.tracer.Start(c.Request.Context(), "handle http request")
	defer span.End()

	c.Request = c.Request.WithContext(ctx)

	t.checkRequestIDExistsAndCreateItIfIsNotSet(ctx, c, span)

	input, err := t.extractInputWithContext(ctx, c)
	if err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to bind input")
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	formData, err := t.createFormDataWithInput(ctx, input)
	if err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to create form data")
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	response, err := t.tryToSendFormDataViaProxyMultipleTimes(ctx, c, formData)
	if err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to send form data via proxy")
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	if response.Status == SberStatusFail {
		c.JSON(http.StatusOK, gin.H{"data": []any{}})
		return
	}

	c.JSON(http.StatusOK, gin.H{"data": []any{response}})
}

func (t *Handler) checkRequestIDExistsAndCreateItIfIsNotSet(ctx context.Context, c *gin.Context, span trace.Span) {
	var requestID string
	if requestID = c.Request.Header.Get("X-Request-ID"); requestID == "" {
		requestID = uuid.NewString()
		c.Request.Header.Set("X-Request-ID", requestID)
	}
	span.SetAttributes(attribute.String("request_id", requestID))
	logrus.WithContext(ctx).WithField("request_id", requestID).Info("request id detected")
}

func (t *Handler) extractInputWithContext(ctx context.Context, c *gin.Context) (*Input, error) {
	input := new(Input)
	if err := c.ShouldBindJSON(input); err != nil {
		return nil, fmt.Errorf("failed to bind input: %w", err)
	}
	logrus.WithContext(ctx).WithField("input", input).Info("input extracted")
	return input, nil
}

func (t *Handler) createFormDataWithInput(ctx context.Context, input *Input) (url.Values, error) {
	formData := url.Values{}
	switch {
	case input.Email != "":
		formData.Set("email", input.Email)
	case input.Phone != "":
		formData.Set("phone", input.Phone)
	default:
		return nil, fmt.Errorf("no email or phone provided")
	}
	logrus.WithContext(ctx).WithField("form_data", formData).Info("form data created")
	return formData, nil
}

func (t *Handler) tryToSendFormDataViaProxyMultipleTimes(ctx context.Context, c *gin.Context, formData url.Values) (*SberResponse, error) {
	ctx, span := t.tracer.Start(ctx, "try to send form data via proxy multiple times")
	defer span.End()

	for i := 0; i < 10; i++ {
		response, err := t.tryToSendFormDataViaProxy(ctx, c, formData)
		if err != nil {
			logrus.WithContext(ctx).Warnf("failed to send form data via proxy: %v", err)
			continue
		}

		return response, nil
	}

	return nil, fmt.Errorf("failed to send form data via proxy")
}

func (t *Handler) tryToSendFormDataViaProxy(ctx context.Context, c *gin.Context, formData url.Values) (*SberResponse, error) {
	ctx, span := t.tracer.Start(ctx, "try to send form data via proxy")
	defer span.End()

	ctx, cancel := context.WithTimeout(ctx, 5*time.Second)
	defer cancel()

	req, err := http.NewRequestWithContext(
		ctx, http.MethodPost, os.Getenv("SBER_ENDPOINT"),
		strings.NewReader(formData.Encode()),
	)
	if err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to create request")
		return nil, fmt.Errorf("failed to create request: %w", err)
	}

	ja3 := t.ja3Loader.Random()

	req.Header.Set("Content-Type", "application/x-www-form-urlencoded")
	req.Header.Set("User-Agent", ja3.UserAgent)
	req.Header.Set("X-Request-Id", c.Request.Header.Get("X-Request-Id"))
	req.Header.Set("X-Sphere-Ja3", ja3.Ja3Str)
	req.Header.Set("X-Sphere-Proxy-Spec-Country-Code", "ru")
	req.Header.Set("X-Sphere-Proxy-Spec-Group-Id", "5")

	logrus.WithContext(ctx).WithField("req", req).Info("request created")

	resp, err := t.client.Do(req)
	if err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to send request")
		return nil, fmt.Errorf("failed to send request: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	logrus.WithContext(ctx).WithField("status_code", resp.StatusCode).Info("response received")

	if resp.StatusCode != http.StatusOK {
		logrus.WithContext(ctx).WithField("status_code", resp.StatusCode).Info("response received")
		return nil, fmt.Errorf("unexpected statatus code: %d", resp.StatusCode)
	}

	var response SberResponse
	if err := json.NewDecoder(resp.Body).Decode(&response); err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to decode response body")
		return nil, fmt.Errorf("failed to decode response body: %w", err)
	}

	return &response, nil
}
