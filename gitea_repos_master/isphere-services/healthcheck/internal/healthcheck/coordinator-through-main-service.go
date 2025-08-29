package healthcheck

import (
	"bytes"
	"context"
	"encoding/xml"
	"errors"
	"fmt"
	"io"
	"net"
	"net/http"
	"net/url"
	"time"

	"github.com/google/uuid"
	"i-sphere.ru/healthcheck/internal/contract"
	"i-sphere.ru/healthcheck/internal/env"
	"i-sphere.ru/healthcheck/internal/storage"
	"i-sphere.ru/healthcheck/internal/util"
)

type CoordinatorThroughMainService struct {
	params *env.Params
}

func NewCoordinatorThroughMainService(params *env.Params) *CoordinatorThroughMainService {
	return &CoordinatorThroughMainService{
		params: params,
	}
}

func (c *CoordinatorThroughMainService) Name() string {
	return "coordinator-through-main-service"
}

func (c *CoordinatorThroughMainService) InspectionInterval() time.Duration {
	return 1 * time.Minute
}

func (c *CoordinatorThroughMainService) Destinations() []contract.HealthcheckDestination {
	return []contract.HealthcheckDestination{
		contract.HealthcheckDestinationServer,
	}
}

func (c *CoordinatorThroughMainService) Check(ctx context.Context, events *storage.Events) error {
	requestID := uuid.New()
	events.RequestID = &requestID

	requestURL, err := url.Parse(fmt.Sprintf("%s/index_new.php", c.params.MainServiceHost))
	if err != nil {
		return fmt.Errorf("failed to parse url: %w", err)
	}

	request := newMainServiceRequest("185.158.155.34", requestID.String())
	request.UserIP = c.localIP()
	request.UserID = c.params.MainServiceUsername
	request.Password = c.params.MainServicePassword

	requestBytes, err := xml.Marshal(request)
	if err != nil {
		return fmt.Errorf("failed to marshal request: %w", err)
	}

	req, err := http.NewRequestWithContext(ctx, http.MethodPost, requestURL.String(), bytes.NewReader(requestBytes))
	if err != nil {
		return fmt.Errorf("failed to create request: %w", err)
	}
	req.Header.Set("Content-Type", "application/xml")
	req.Header.Set("Accept", "application/xml")
	req.Header.Set("X-Request-ID", requestID.String())

	event := storage.NewEvent("http-request", c.params.MainServiceHost).
		With("request", map[string]any{
			"method":  req.Method,
			"url":     req.URL.String(),
			"headers": string(util.MustMarshal(util.NormalizeHeaders(req.Header))),
			"body":    string(bytes.ReplaceAll(requestBytes, []byte(c.params.MainServicePassword), []byte("********"))),
		})

	start := time.Now()
	resp, err := http.DefaultClient.Do(req)
	event = event.WithDuration(time.Since(start))
	if err != nil {
		events.Append(event.WithError(err))
		return fmt.Errorf("failed to send request: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	respBytes, err := io.ReadAll(resp.Body)
	if err != nil {
		events.Append(event.WithError(err))
		return fmt.Errorf("failed to read response body: %w", err)
	}
	event = event.With("response", map[string]any{
		"status_code": resp.StatusCode,
		"headers":     string(util.MustMarshal(util.NormalizeHeaders(resp.Header))),
		"body":        string(respBytes),
	})

	var response mainServiceResponse
	if err := xml.Unmarshal(respBytes, &response); err != nil {
		events.Append(event.WithError(err))
		return fmt.Errorf("failed to decode response: %w", err)
	}
	if response.Status != 1 {
		err := fmt.Errorf("unexpected status: %d: with diagnostic: %s", response.Status, response.View)
		events.Append(event.WithError(err))
		return err
	}
	if response.Source == nil {
		err := errors.New("source is nil")
		events.Append(event.WithError(err))
		return err
	}
	if response.Source.ResultsCount != 1 {
		err := fmt.Errorf("unexpected results count: %d", response.Source.ResultsCount)
		events.Append(event.WithError(err))
		return err
	}

	events.Append(event)
	return nil
}

func (c *CoordinatorThroughMainService) localIP() string {
	ifaces, err := net.Interfaces()
	if err == nil {
		for _, i := range ifaces {
			addrs, err := i.Addrs()
			if err == nil {
				for _, addr := range addrs {
					switch v := addr.(type) {
					case *net.IPNet:
						return v.IP.String()
					case *net.IPAddr:
						return v.IP.String()
					}
				}
			}
		}
	}
	return ""
}

type mainServiceRequest struct {
	XMLName     xml.Name    `xml:"Request"`
	UserIP      string      `xml:"UserIP"`
	UserID      string      `xml:"UserID"`
	Password    string      `xml:"Password"`
	RequestType requestType `xml:"requestType"`
	RequestID   string      `xml:"requestId"`
	Sources     string      `xml:"sources"`
	Timeout     int         `xml:"timeout"`
	Recursive   int         `xml:"recursive"`
	Async       int         `xml:"async"`
	IPReq       *ipReq      `xml:"IPReq"`
}

type ipReq struct {
	IP string `xml:"ip"`
}

func newMainServiceRequest(ip string, requestID string) *mainServiceRequest {
	return &mainServiceRequest{
		UserIP:      ip,
		RequestType: requestTypeCheckIP,
		RequestID:   requestID,
		Sources:     "geoip",
		Timeout:     30,
		Recursive:   0,
		Async:       0,
		IPReq: &ipReq{
			IP: ip,
		},
	}
}

type requestType string

const requestTypeCheckIP requestType = "checkip"

type mainServiceResponse struct {
	XMLName  xml.Name            `xml:"Response"`
	ID       int                 `xml:"id,attr"`
	Status   int                 `xml:"status,attr"`
	DateTime string              `xml:"datetime,attr"`
	Result   string              `xml:"result,attr"`
	View     string              `xml:"view,attr"`
	Request  *mainServiceRequest `xml:"Request"`
	Source   *source             `xml:"Source"`
}

type source struct {
	Code         string    `xml:"code,attr"`
	CheckType    string    `xml:"checktype,attr"`
	Start        string    `xml:"start,attr"`
	Param        string    `xml:"param,attr"`
	Path         string    `xml:"path,attr"`
	Level        int       `xml:"level,attr"`
	Index        int       `xml:"index,attr"`
	RequestID    string    `xml:"request_id,attr"`
	ProcessTime  float64   `xml:"process_time,attr"`
	Name         string    `xml:"Name"`
	Title        string    `xml:"Title"`
	CheckTitle   string    `xml:"CheckTitle"`
	Request      string    `xml:"Request"`
	ResultsCount int       `xml:"ResultsCount"`
	Records      []*record `xml:"Record"`
}

type record struct {
	Fields []*field `xml:"Field"`
}

type field struct {
	FieldType        string `xml:"FieldType"`
	FieldName        string `xml:"FieldName"`
	FieldDescription string `xml:"FieldDescription"`
	FieldValue       string `xml:"FieldValue"`
}
