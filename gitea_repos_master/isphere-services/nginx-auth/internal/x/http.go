package x

import (
	"bytes"
	"encoding/base64"
	"encoding/json"
	"fmt"
	"log/slog"
	"net"
	"net/http"
	"strconv"
	"strings"
	"time"

	"i-sphere.ru/nginx-auth/internal/contract"

	"i-sphere.ru/nginx-auth/internal/tcp"
)

func ClientAddr(req *http.Request) string {
	ip := req.Header.Get("X-Real-Ip")
	if ip == "" {
		ip = req.Header.Get("X-Forwarded-For")
	}
	if ip == "" {
		ip = req.RemoteAddr
	}
	return ip
}

func ClientIP(req *http.Request) net.IP {
	clientAddr := ClientAddr(req)
	clientIP, _, err := net.SplitHostPort(clientAddr)
	if err != nil {
		slog.With("error", err).ErrorContext(req.Context(), "failed to parse client IP")
		return nil
	}
	return net.ParseIP(clientIP)
}

func Problem(respWriter http.ResponseWriter, req *http.Request, err error, statusCode int) {
	respWriter.Header().Set("Content-Type", "application/problem+json")
	respWriter.WriteHeader(statusCode)

	p := &problem{
		Title:  http.StatusText(statusCode),
		Status: statusCode,
		Detail: err.Error(),
		Type:   "about:blank",
	}

	if err := json.NewEncoder(respWriter).Encode(p); err != nil {
		respWriter.Header().Set("Content-Type", "text/plain")
		slog.With("err", err).ErrorContext(req.Context(), "failed to write error")
	}
}

type problem struct {
	Title  string `json:"title"`
	Status int    `json:"status"`
	Detail string `json:"detail"`
	Type   string `json:"type"`
}

func SplitHostPortSafe(req *http.Request) (string, string) {
	host, portStr, err := net.SplitHostPort(req.Host)
	if err != nil {
		host = req.Host
		if req.TLS != nil {
			portStr = "443"
		} else {
			portStr = "80"
		}
	}
	return host, portStr
}

//goland:noinspection GoExportedFuncWithUnexportedType
func NewLogContext(respWriter *tcp.MemoryRecorder, req *http.Request, clientHello *TLSPlaintext, start *time.Time, countryCode *string) *logContext {
	var username string
	if authorization := req.Header.Get("Authorization"); authorization != "" {
		if _, encoded, ok := strings.Cut(authorization, " "); ok { // split on space and get the second part
			if data, err := base64.StdEncoding.DecodeString(encoded); err == nil {
				if user, _, ok := bytes.Cut(data, []byte(":")); ok {
					username = string(user)
				}
			}
		}
	}

	var ja3Str string
	var ja3Hash string
	if clientHello != nil {
		ja3Str = clientHello.JA3()
		if ja3Str == ",,,," {
			ja3Str = ""
		} else {
			ja3Hash = MD5Hash(ja3Str)
		}
	}

	return &logContext{
		RemoteAddr:    ClientAddr(req),
		RemoteUser:    Ptr(username),
		TimeLocal:     *start,
		TimeDuration:  timeDuration(time.Since(*start)),
		RequestMethod: req.Method,
		RequestURI:    Coalesce(req.RequestURI, req.URL.RequestURI()),
		RequestProto:  req.Proto,
		Status:        respWriter.StatusCode,
		BodyBytesSent: respWriter.BodySize,
		HTTPReferer:   Ptr(req.Referer()),
		HTTPUserAgent: Ptr(req.UserAgent()),
		JA3:           Ptr(ja3Str),
		JA3Hash:       Ptr(ja3Hash),
		CountryCode:   countryCode,
	}
}

func (c *logContext) String() string {
	sb := strings.Builder{}

	// remote_addr
	sb.WriteString(c.RemoteAddr)
	sb.WriteString(" - ")

	// remote_user
	sb.WriteString(Coalesce(Value(c.RemoteUser), "-"))
	sb.WriteRune(' ')

	// time_local
	sb.WriteString(Wrap(c.TimeLocal.Format(contract.NginxTimeLayout), "[", "]"))
	sb.WriteRune(' ')

	// request
	sb.WriteString(WrapQ(fmt.Sprintf("%s %s %s", c.RequestMethod, c.RequestURI, c.RequestProto)))
	sb.WriteRune(' ')

	// status
	sb.WriteString(strconv.Itoa(c.Status))
	sb.WriteRune(' ')

	// body_bytes_sent
	sb.WriteString(strconv.Itoa(c.BodyBytesSent))
	sb.WriteRune(' ')

	// http_referer
	sb.WriteString(WrapQ(Value(c.HTTPReferer)))
	sb.WriteRune(' ')

	// http_user_agent
	sb.WriteString(WrapQ(Value(c.HTTPUserAgent)))
	sb.WriteRune(' ')

	// ja3
	sb.WriteString(WrapQ(Value(c.JA3Hash)))

	return sb.String()
}

type logContext struct {
	RemoteAddr    string       `json:"remote_addr"`
	RemoteUser    *string      `json:"remote_user"`
	TimeLocal     time.Time    `json:"time_local"`
	TimeDuration  timeDuration `json:"time_duration"`
	RequestMethod string       `json:"request_method"`
	RequestURI    string       `json:"request_uri"`
	RequestProto  string       `json:"request_proto"`
	Status        int          `json:"status"`
	BodyBytesSent int          `json:"body_bytes_sent"`
	HTTPReferer   *string      `json:"http_referer"`
	HTTPUserAgent *string      `json:"http_user_agent"`
	JA3           *string      `json:"ja3"`
	JA3Hash       *string      `json:"ja3_hash"`
	CountryCode   *string      `json:"country_code"`
}

type timeDuration time.Duration

func (t *timeDuration) MarshalJSON() ([]byte, error) {
	serialized, err := json.Marshal(time.Duration(*t).String())
	if err != nil {
		return nil, fmt.Errorf("failed to marshal time.Duration: %w", err)
	}

	return serialized, nil
}
