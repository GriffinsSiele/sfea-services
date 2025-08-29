package firewall

import (
	"context"
	"log/slog"
	"net"
	"time"

	"github.com/doug-martin/goqu/v9"
	"go.i-sphere.ru/ispherix/internal/clickhouse"
)

type ConnStat struct {
	ClientAddr        net.Addr
	ClientCountryCode string
	ClientFingerprint string
	ServerName        string
	ServerAddr        net.Addr
	ServerFingerprint string
	Error             error
	StartTime         time.Time
	EndTime           time.Time

	logger *slog.Logger
}

func NewConnStat(logger *slog.Logger, clientAddr net.Addr) *ConnStat {
	return &ConnStat{
		ClientAddr: clientAddr,
		StartTime:  time.Now(),

		logger: logger,
	}
}

func (o *ConnStat) Flush(ctx context.Context, clickhousePool *clickhouse.Pool) {
	o.InfoContext(ctx, "handled connection")

	clickhouseConn, err := clickhousePool.Acquire(ctx)
	if err != nil {
		slog.ErrorContext(ctx, "failed to acquire clickhouse connection", "error", err)
		return
	}

	record := goqu.Record{}

	if o.ClientAddr != nil {
		if clientTCPAddr, ok := o.ClientAddr.(*net.TCPAddr); ok {
			record["client_ip"] = clientTCPAddr.IP.String()
			record["client_port"] = clientTCPAddr.Port
		}
	}

	if o.ClientCountryCode != "" {
		record["client_country_code"] = o.ClientCountryCode
	}

	if o.ClientFingerprint != "" {
		record["client_fingerprint"] = o.ClientFingerprint
	}

	if o.ServerName != "" {
		record["server_name"] = o.ServerName
	}

	if o.ServerAddr != nil {
		if serverTCPAddr, ok := o.ServerAddr.(*net.TCPAddr); ok {
			record["server_ip"] = serverTCPAddr.IP.String()
			record["server_port"] = serverTCPAddr.Port
		}
	}

	if o.ServerFingerprint != "" {
		record["server_fingerprint"] = o.ServerFingerprint
	}

	if o.Error != nil {
		record["error"] = o.Error.Error()
	}

	if o.StartTime != (time.Time{}) {
		record["start_time"] = o.StartTime
	}

	if o.EndTime != (time.Time{}) {
		record["end_time"] = o.EndTime
	}

	query, params, err := goqu.Insert("ispherix.firewall_logs").Rows(record).Prepared(true).ToSQL()
	if err != nil {
		slog.ErrorContext(ctx, "failed to build query", "error", err)
		return
	}

	if err = clickhouseConn.Exec(ctx, query, params...); err != nil {
		slog.ErrorContext(ctx, "failed to insert record", "error", err)
	}
}

func (o *ConnStat) DebugContext(ctx context.Context, msg string, args ...any) {
	o.log(ctx, slog.LevelDebug, msg, args...)
}

func (o *ConnStat) InfoContext(ctx context.Context, msg string, args ...any) {
	o.log(ctx, slog.LevelInfo, msg, args...)
}

func (o *ConnStat) WarnContext(ctx context.Context, msg string, args ...any) {
	o.log(ctx, slog.LevelWarn, msg, args...)
}

func (o *ConnStat) ErrorContext(ctx context.Context, msg string, args ...any) {
	o.log(ctx, slog.LevelError, msg, args...)
}

func (o *ConnStat) log(ctx context.Context, level slog.Level, msg string, args ...any) {
	var logAttrs []any

	if o.ClientAddr != nil {
		if clientTCPAddr, ok := o.ClientAddr.(*net.TCPAddr); ok {
			logAttrs = append(
				logAttrs,
				slog.String("client_ip", clientTCPAddr.IP.String()),
				slog.Int("client_port", clientTCPAddr.Port),
			)
		} else {
			logAttrs = append(logAttrs, slog.String("client_addr", o.ClientAddr.String()))
		}
	}

	if o.ClientCountryCode != "" {
		logAttrs = append(logAttrs, slog.String("client_country_code", o.ClientCountryCode))
	}

	if o.ClientFingerprint != "" {
		logAttrs = append(logAttrs, slog.String("client_fingerprint", o.ClientFingerprint))
	}

	if o.ServerName != "" {
		logAttrs = append(logAttrs, slog.String("server_name", o.ServerName))
	}

	if o.ServerAddr != nil {
		if serverTCPAddr, ok := o.ServerAddr.(*net.TCPAddr); ok {
			logAttrs = append(
				logAttrs,
				slog.String("server_ip", serverTCPAddr.IP.String()),
				slog.Int("server_port", serverTCPAddr.Port),
			)
		} else {
			logAttrs = append(logAttrs, slog.String("server_addr", o.ServerAddr.String()))
		}
	}

	if o.ServerFingerprint != "" {
		logAttrs = append(logAttrs, slog.String("server_fingerprint", o.ServerFingerprint))
	}

	if o.Error != nil {
		logAttrs = append(logAttrs, slog.String("error", o.Error.Error()))
	}

	if o.StartTime != (time.Time{}) {
		logAttrs = append(logAttrs, slog.Time("start_time", o.StartTime))
	}

	if o.EndTime != (time.Time{}) {
		logAttrs = append(logAttrs, slog.Time("end_time", o.EndTime))
	}

	logAttrs = append(logAttrs, args...)

	o.logger.Log(ctx, level, msg, logAttrs...)
}
