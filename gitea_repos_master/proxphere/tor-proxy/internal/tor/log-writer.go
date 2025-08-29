package tor

import (
	"bytes"
	"log/slog"
)

type LogWriter struct {
	log *slog.Logger
}

func NewLogWriter(log *slog.Logger) *LogWriter {
	return &LogWriter{
		log: log,
	}
}

func (w *LogWriter) Write(p []byte) (int, error) {
	w.log.Info(string(bytes.TrimSpace(p)))
	return len(p), nil
}
