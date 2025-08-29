package managers

import (
	"context"
	"time"

	"github.com/charmbracelet/log"
	"go.i-sphere.ru/proxy/pkg/clients"
	"go.i-sphere.ru/proxy/pkg/utils"
)

type ProxySpec struct {
	clickhouse *clients.Clickhouse

	logger *log.Logger
}

func NewProxySpec(clickhouse *clients.Clickhouse) *ProxySpec {
	return &ProxySpec{
		clickhouse: clickhouse,

		logger: log.WithPrefix("managers.ProxySpec"),
	}
}

func (p *ProxySpec) LogRequestResponse(options *ProxySpecLogOptions) error {
	return p.LogRequestResponseWithContext(context.Background(), options)
}

type proxySpecLogStruct struct {
	ProxySpecID        *int           `json:"proxy_spec_id,omitempty"`
	ProxySpecGroupID   *int           `json:"proxy_spec_group_id,omitempty"`
	RequestHost        *string        `json:"request_host,omitempty"`
	ResponseStatusCode *int           `json:"response_status_code,omitempty"`
	Error              *string        `json:"error,omitempty"`
	Duration           *time.Duration `json:"duration,omitempty"`
	Master             *bool          `json:"master,omitempty"`
	Timestamp          *time.Time     `json:"timestamp,omitempty"`
}

func (p *ProxySpec) LogRequestResponseWithContext(ctx context.Context, options *ProxySpecLogOptions) error {
	proxySpecLog := &proxySpecLogStruct{}

	// ProxySpecID
	if s := options.ProxySpec; s != nil && s.ID > 0 {
		id := int(s.ID)
		proxySpecLog.ProxySpecID = &id

		// ProxySpecGroupID
		if g := s.Group; g != nil && g.ID > 0 {
			gid := int(g.ID)
			proxySpecLog.ProxySpecGroupID = &gid
		}
	}

	// RequestHost
	if req := options.Request; req != nil {
		requestHost := utils.Coalesce(req.Host, req.URL.Hostname())
		proxySpecLog.RequestHost = &requestHost
	}

	// ResponseStatusCode
	if resp := options.Response; resp != nil && resp.StatusCode > 0 {
		statusCode := resp.StatusCode
		proxySpecLog.ResponseStatusCode = &statusCode
	}

	// Error
	if err := options.Error; err != nil {
		errMessage := err.Error()
		proxySpecLog.Error = &errMessage
	}

	// Duration
	if d := options.Duration(); d != nil {
		proxySpecLog.Duration = d
	}

	// Master
	proxySpecLog.Master = &options.Master

	// Timestamp
	proxySpecLog.Timestamp = &options.RequestCreatedAt

	go p.logProxySpecLog(proxySpecLog)

	return nil
}

func (p *ProxySpec) logProxySpecLog(proxySpecLog *proxySpecLogStruct) {
	if proxySpecLog.ProxySpecID == nil {
		return
	}

	cancelCtx, cancel := context.WithTimeout(context.Background(), 3*time.Second)
	defer cancel()

	tx, err := p.clickhouse.Begin()
	if err != nil {
		p.logger.Error("failed to begin transaction", "error", err)
		return
	}
	//goland:noinspection GoUnhandledErrorResult
	defer tx.Rollback()

	stmt, err := tx.PrepareContext(cancelCtx,
		`insert into proxy_spec_logs_direct (proxy_spec_id,
                                    proxy_spec_group_id,
                                    request_host,
                                    response_status_code,
                                    error,
                                    duration,
                                    timestamp)
values (?, ?, ?, ?, ?, ?, ?, ?)`)
	if err != nil {
		p.logger.Error("failed to prepare insert statement", "error", err)
		return
	}

	durationAsFloat := func(d *time.Duration) *float64 {
		if d == nil {
			return nil
		}
		s := d.Seconds()
		return &s
	}

	props := []any{
		proxySpecLog.ProxySpecID,
		proxySpecLog.ProxySpecGroupID,
		proxySpecLog.RequestHost,
		proxySpecLog.ResponseStatusCode,
		proxySpecLog.Error,
		durationAsFloat(proxySpecLog.Duration),
		proxySpecLog.Timestamp,
	}

	if _, err = stmt.ExecContext(cancelCtx, props...); err != nil {
		p.logger.Error("failed to execute insert statement", "error", err, "props", props)
		return
	}

	if err = tx.Commit(); err != nil {
		p.logger.Error("failed to commit transaction", "error", err)
	}
}
