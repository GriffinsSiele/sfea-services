package handler

// @see https://redsms.ru/integration/api/https/

import (
	"crypto/md5"
	"encoding/hex"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"net/http"
	"net/url"
	"os"
	"sort"
	"strconv"
	"strings"
	"time"

	"git.i-sphere.ru/isphere-go-modules/hlr/internal/dto"
	"github.com/gin-gonic/gin"
	"github.com/google/uuid"
	"github.com/mitchellh/mapstructure"
	"github.com/sirupsen/logrus"
)

func Start(c *gin.Context) {
	var msg dto.StartReq

	if err := c.ShouldBindJSON(&msg); err != nil {
		_ = c.AbortWithError(http.StatusBadRequest, fmt.Errorf("failed to bind input: %w", err))

		return
	}

	req := NewReq().
		WithRoute(ReqRouteHLR).
		WithTo(msg.Tel).
		WithClientField(c.GetHeader("X-Message-ID"))

	if err := req.Sign(); err != nil {
		_ = c.AbortWithError(http.StatusInternalServerError, fmt.Errorf("cannot sign req: %w", err))

		return
	}

	var o map[string]any
	if err := mapstructure.Decode(req, &o); err != nil {
		_ = c.AbortWithError(http.StatusInternalServerError, fmt.Errorf("failed to decode mapstructure finalized object: %w", err))

		return
	}

	formData := url.Values{}
	for k, v := range o {
		formData.Add(k, fmt.Sprintf("%v", v))
	}

	res, err := http.PostForm(os.Getenv("REDSMS_ENDPOINT"), formData)

	logrus.WithFields(logrus.Fields{
		"url":  os.Getenv("REDSMS_ENDPOINT"),
		"data": formData,
		"err":  err,
	}).Info("send request")

	if err != nil {
		_ = c.AbortWithError(http.StatusBadGateway, fmt.Errorf("failed to send hlr request: %w", err))

		return
	}

	//goland:noinspection GoUnhandledErrorResult
	defer res.Body.Close()

	rawResponse, err := io.ReadAll(res.Body)
	if err != nil {
		_ = c.AbortWithError(http.StatusInternalServerError, fmt.Errorf("failed to read hlr response: %w", err))

		return
	}

	var resData Res
	if err = json.Unmarshal(rawResponse, &resData); err != nil {
		_ = c.AbortWithError(http.StatusInternalServerError, fmt.Errorf("failed to unmarshal response: %w", err))

		return
	}

	logrus.WithFields(logrus.Fields{
		"url":              os.Getenv("REDSMS_ENDPOINT"),
		"request_data":     formData,
		"response_headers": res.Header,
		"response_data":    string(rawResponse),
	}).Debug("trace request")

	if !resData.Success || len(resData.Errors) > 0 {
		var errs []string

		if resData.ErrorMessage != "" {
			errs = append(errs, resData.ErrorMessage)
		}

		for _, err := range resData.Errors {
			errs = append(errs, err.Message)
		}

		_ = c.AbortWithError(http.StatusConflict, fmt.Errorf("error sending: %v", errs))

		return
	}

	if len(resData.Items) != 1 {
		_ = c.AbortWithError(http.StatusConflict, errors.New("unexpected items count"))

		return
	}

	c.JSON(http.StatusAccepted, gin.H{"id": resData.Items[0].UUID})
}

// ---

var ReqExcludedSignatureKeys = []string{"login", "sig", "ts"}

type Req struct {
	Login       string    `mapstructure:"login"`
	Timestamp   string    `mapstructure:"ts"`
	Signature   string    `mapstructure:"sig"`
	Format      ReqFormat `mapstructure:"format"`
	Route       ReqRoute  `mapstructure:"route"`
	To          string    `mapstructure:"to"`
	ClientField string    `mapstructure:"clientField"`
}

func NewReq() *Req {
	return &Req{
		Login:     os.Getenv("REDSMS_LOGIN"),
		Timestamp: strconv.FormatInt(time.Now().UnixMicro(), 10),
		Format:    ReqFormatJSON,
	}
}

func (t *Req) WithClientField(text string) *Req {
	t.ClientField = text

	return t
}

func (t *Req) WithRoute(route ReqRoute) *Req {
	t.Route = route

	return t
}

func (t *Req) WithTo(to string) *Req {
	t.To = to

	return t
}

func (t *Req) Sign() error {
	var o map[string]any
	if err := mapstructure.Decode(t, &o); err != nil {
		return fmt.Errorf("failed to decode mapstructure: %w", err)
	}

	for _, k := range ReqExcludedSignatureKeys {
		delete(o, k)
	}

	keys := make([]string, 0, len(o))
	for k := range o {
		keys = append(keys, k)
	}

	sort.Strings(keys)

	var sb strings.Builder
	for _, k := range keys {
		sb.WriteString(fmt.Sprintf("%v", o[k]))
	}

	sb.WriteString(t.Timestamp)
	sb.WriteString(os.Getenv("REDSMS_PASSWORD"))

	hash := md5.Sum([]byte(sb.String()))

	t.Signature = hex.EncodeToString(hash[:])

	return nil
}

// ---

type ReqRoute string

const ReqRouteHLR ReqRoute = "hlr"

// ---

type ReqFormat string

const ReqFormatJSON ReqFormat = "json"

// ---

type Res struct {
	Items        []*ResItem  `json:"items"`
	ErrorMessage string      `json:"error_message"`
	Errors       []*ResError `json:"errors"`
	Count        int         `json:"count"`
	Success      bool        `json:"success"`
}

type ResItem struct {
	UUID       uuid.UUID     `json:"uuid"`
	Status     ResItemStatus `json:"status"`
	StatusTime int64         `json:"status_time"`
	To         string        `json:"to"`
}

type ResError struct {
	From    string `json:"from"`
	To      string `json:"to"`
	Message string `json:"message"`
}

// ---

type ResItemStatus string

const (
	ResItemStatusCreated       ResItemStatus = "created"
	ResItemStatusModeration    ResItemStatus = "moderation"
	ResItemStatusReject        ResItemStatus = "reject"
	ResItemStatusDelivered     ResItemStatus = "delivered"
	ResItemStatusRead          ResItemStatus = "read"
	ResItemStatusReply         ResItemStatus = "reply"
	ResItemStatusUndelivered   ResItemStatus = "undelivered"
	ResItemStatusTimeout       ResItemStatus = "timeout"
	ResItemStatusProgress      ResItemStatus = "progress"
	ResItemStatusNoMoney       ResItemStatus = "no_money"
	ResItemStatusDoubled       ResItemStatus = "doubled"
	ResItemStatusLimitExceeded ResItemStatus = "limit_exceeded"
	ResItemStatusBadNumber     ResItemStatus = "bad_number"
	ResItemStatusStopList      ResItemStatus = "stop_list"
	ResItemStatusRouteClosed   ResItemStatus = "route_closed"
	ResItemStatusError         ResItemStatus = "error"
)
