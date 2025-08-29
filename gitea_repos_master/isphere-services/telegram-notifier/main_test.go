package main

import (
	"bytes"
	"io"
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/gin-gonic/gin"
	"github.com/stretchr/testify/assert"
)

func TestHandle(t *testing.T) {
	t.Parallel()

	assert.NoError(t, loadEnv())

	respWriter := &httptest.ResponseRecorder{}
	ctx, _ := gin.CreateTestContext(respWriter)
	ctx.Request = &http.Request{
		Body: io.NopCloser(bytes.NewReader(bytes.TrimSpace([]byte(`
{
	"text": "GlitchIP Alert",
	"attachments": [
		{
			"color": "#FF0000",
			"title": "Alert Details",
			"fields": [
				{
					"title": "IP Address",
					"value": "192.168.1.1",
					"short": true
				},
				{
					"title": "Location",
					"value": "San Francisco, CA",
					"short": true
				},
				{
					"title": "Timestamp",
					"value": "2022-01-01 12:00:00",
					"short": true
				}
			],
			"footer": "GlitchIP",
			"footer_icon": "https://example.com/glitchip_logo.png",
			"ts": 1678900400
		}
	]
}
			`)))),
	}

	handle(ctx)

	assert.Equal(t, http.StatusOK, respWriter.Result().StatusCode)
}
