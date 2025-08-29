package tcp

import (
	"bufio"
	"context"
	"crypto/tls"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"net/http/httputil"
	"net/url"
	"regexp"
	"strings"

	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/config"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/util"
	"github.com/sirupsen/logrus"
)

type Conn struct {
	*http.Client
	Endpoint *url.URL
}

func (c *Conn) Release() {}

func (c *Conn) Exec(ctx context.Context, query string, result any, variables map[string]any) (int, error) {
	req, err := c.buildRequest(ctx, query, variables)
	if err != nil {
		return 0, fmt.Errorf("failed to build request: %w", err)
	}

	reqBytes, err := httputil.DumpRequestOut(req, true)
	if err != nil {
		return 0, fmt.Errorf("failed to dump request: %w", err)
	}

	var ok = util.Ptr(false)
	log := logrus.WithContext(ctx).WithField("request", string(reqBytes))
	defer func(logContext *logrus.Entry, ok *bool) {
		if *ok {
			logContext.Debug("executed request successfully")
		} else {
			logContext.Warnf("failed to execute request")
		}
	}(log, ok)

	var retry *config.UpstreamTCPRetry
	var retryMaxCount = 1
	if r, hasRetryFeature := ContextGetRetry(req.Context()); hasRetryFeature {
		retry = r
		retryMaxCount = retry.MaxCount
	}

	var resp *http.Response
	var respErr error

	for i := 0; i < retryMaxCount; i++ {
		if resp, respErr = c.Do(req); respErr != nil {
			logrus.WithContext(ctx).WithError(respErr).Warn("failed to execute request")
			continue
		}
		if retry != nil {
			if resp.StatusCode == retry.ExpectedStatusCode {
				break
			} else {
				//goland:noinspection GoUnhandledErrorResult
				resp.Body.Close()
				logrus.WithContext(ctx).WithError(respErr).Warn("unexpected status code will retry again")
				continue
			}
		} else {
			break
		}
	}

	if respErr != nil {
		return 0, respErr
	}
	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	respBytes, err := httputil.DumpResponse(resp, true)
	if err != nil {
		return 0, fmt.Errorf("failed to dump response: %w", err)
	}

	*ok = *util.Ptr(resp.StatusCode < 400)
	*log = *log.WithField("response", string(respBytes))

	respBodyBytes, err := io.ReadAll(resp.Body)
	if err != nil {
		return 0, fmt.Errorf("failed to read response body: %w", err)
	}

	if len(respBodyBytes) > 0 {
		if err = json.Unmarshal(respBodyBytes, result); err != nil {
			return 0, fmt.Errorf("failed to decode response: %w", err)
		}
	}

	return resp.StatusCode, nil
}

func (c *Conn) Do(req *http.Request) (*http.Response, error) {
	proxy, ok := ContextGetProxy(req.Context())
	if !ok {
		return http.DefaultClient.Do(req)
	}

	proxyURL, err := url.Parse(proxy.URL)
	if err != nil {
		return nil, fmt.Errorf("failed to parse proxy url: %w", err)
	}

	return (&http.Client{
		Transport: &http.Transport{
			Proxy: http.ProxyURL(proxyURL),
			TLSClientConfig: &tls.Config{
				InsecureSkipVerify: true,
			},
		},
	}).Do(req)
}

func (c *Conn) buildRequest(ctx context.Context, query string, variables map[string]any) (*http.Request, error) {
	query = strings.ReplaceAll(query, "\r", "")
	query = strings.ReplaceAll(query, "\n", "\r\n")

	re := regexp.MustCompile(`(?m){{\s*([^\s\}]+)\s*}}`)

	query = re.ReplaceAllStringFunc(query, func(key string) string {
		key = strings.Trim(key, "{} ")

		return fmt.Sprintf("%v", variables[key])
	})

	query = strings.TrimSpace(query)

	blocks := strings.Split(query, "\r\n\r\n")
	blocks[0] += "\r\n\r\n"

	var requestBuilder strings.Builder

	requestBuilder.WriteString(blocks[0])

	req, err := http.ReadRequest(bufio.NewReader(strings.NewReader(blocks[0])))
	if err != nil {
		return nil, fmt.Errorf("failed to read request: %w", err)
	}

	if len(blocks) > 1 {
		req.Body = io.NopCloser(strings.NewReader(blocks[1]))
	}

	reqURL, err := url.Parse(req.RequestURI)
	if err != nil {
		return nil, fmt.Errorf("failed to parse request URL: %w", err)
	}

	reqURL.Scheme, reqURL.Host = c.Endpoint.Scheme, c.Endpoint.Host
	req.URL, req.RequestURI = reqURL, ""

	return req.WithContext(ctx), nil
}
