package event_listener

import (
	"Golang/utils"
	"bytes"
	"encoding/json"
	"github.com/sirupsen/logrus"
	"io/ioutil"
	"net/http"
	"time"
)

type RequestLoggerEventListener struct {
}

func (t *RequestLoggerEventListener) Invoke(next http.Handler) http.Handler {
	return http.HandlerFunc(func(res http.ResponseWriter, req *http.Request) {
		startTime := time.Now()
		header, _ := json.Marshal(req.Header)
		reqBody, err := ioutil.ReadAll(req.Body)
		log := logrus.WithField("method", req.Method).
			WithField("url", req.RequestURI).
			WithField("header", string(header)).
			WithField("host", req.Host).
			WithField("remote_addr", req.RemoteAddr).
			WithField("request", string(reqBody)).
			WithField("request_time", startTime)
		if err != nil {
			log.WithError(err).Errorf("%+v", err)
			res.WriteHeader(http.StatusInternalServerError)
			_, _ = res.Write([]byte("internal server error"))
			return
		}
		req.Body = ioutil.NopCloser(bytes.NewBuffer(reqBody))

		resLogger := utils.NewResponseLogger(res)
		next.ServeHTTP(resLogger, req)

		go func() {
			endTime := time.Now()
			resBody, _ := ioutil.ReadAll(resLogger.Buf)
			log.WithField("request_duration", endTime.Sub(startTime)).
				WithField("response", string(resBody)).
				WithField("response_status", resLogger.Status).
				Info("request")
		}()
	})
}

func NewRequestLoggerEventListener() *RequestLoggerEventListener {
	return &RequestLoggerEventListener{}
}
