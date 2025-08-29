package main

import (
	"bytes"
	"encoding/json"
	"flag"
	"fmt"
	"html/template"
	"net/http"
	"net/url"
	"os"
	"strings"

	"git.i-sphere.ru/isphere-services/telegram-notifier/dto"
	"github.com/charmbracelet/log"
	"github.com/getsentry/sentry-go"
	"github.com/joho/godotenv"
	"github.com/valyala/fasthttp"
)

var tpl *template.Template

func init() {
	tpl = template.Must(
		template.New("main").Parse(strings.TrimSpace(`
{{ range .Attachments -}}
🐞 {{ (.FieldValue "Project") }}/{{ (.FieldValue "Environment") }}
<b><a href="{{ .TitleLink }}">{{ .Title }}</a></b>
{{ if (not (eq .Text nil)) }}
<pre><code class="language-bash">{{ .Text }}</code></pre>
{{ end }}
{{ end }}
`) + "\n"),
	)
}

func main() {
	var addr string

	flag.StringVar(&addr, "addr", "0.0.0.0:80", "listen address")
	flag.Parse()

	if err := loadEnv(); err != nil {
		log.WithPrefix("main").With("err", err).Fatal("failed to load env files")
	}

	sentry.Init(sentry.ClientOptions{
		Dsn: os.Getenv("SENTRY_DSN"),
	})

	log.SetLevel(log.DebugLevel)

	log.WithPrefix("main").With("addr", addr).Info("started server")

	if err := fasthttp.ListenAndServe("0.0.0.0:80", fasthttpHandler); err != nil {
		log.WithPrefix("main").With("err", err).Fatal("failed to run fasthttp server")
	}
}

func loadEnv() error {
	if err := godotenv.Load(); err != nil {
		return fmt.Errorf("failed to load .env file: %w", err)
	}

	if err := godotenv.Overload(".env.local"); err != nil && !os.IsNotExist(err) {
		return fmt.Errorf("failed to load .env.local file: %w", err)
	}

	return nil
}

func fasthttpHandler(ctx *fasthttp.RequestCtx) {
	defer func() {
		logMessage := fmt.Sprintf(
			"%s - %s [%s] \"%s %s %s\" %d %d \"%s\" \"%s\"", // nginx compatible log format
			ctx.RemoteAddr().String(),                       // remote addr
			ctx.Request.URI().Username(),                    // remote user
			ctx.Time().Format("02/Jan/2006:15:04:05 -0700"), // time local format with nginx
			ctx.Method(),                        // request method
			ctx.RequestURI(),                    // request uri
			ctx.Request.Header.Protocol(),       // request protocol
			ctx.Response.StatusCode(),           // status
			ctx.Response.Header.ContentLength(), // body bytes sent
			ctx.Request.Header.Referer(),        // http referer
			ctx.Request.Header.UserAgent(),      // http user agent
		)

		l := log.WithPrefix("fasthttpHandler")
		switch {
		case ctx.Response.StatusCode() < 300:
			l.Info(logMessage)
		case ctx.Response.StatusCode() < 400:
			l.Debug(logMessage)
		case ctx.Response.StatusCode() < 500:
			l.Warn(logMessage)
		default:
			l.Error(logMessage)
		}
	}()

	if err := fasthttpHandle(ctx); err != nil {
		if err := json.NewEncoder(ctx).Encode(map[string]string{
			"error":   http.StatusText(ctx.Response.StatusCode()),
			"details": err.Error(),
		}); err != nil {
			log.WithPrefix("fasthttpHandler").With("err", err).Error("failed to encode response")
		}

		return
	}
}

func fasthttpHandle(ctx *fasthttp.RequestCtx) error {
	log.WithPrefix("fasthttpHandle").With("request", string(ctx.PostBody())).Debug("received notification")

	var notification dto.Notification

	if err := json.NewDecoder(bytes.NewReader(ctx.PostBody())).Decode(&notification); err != nil {
		ctx.SetStatusCode(fasthttp.StatusUnprocessableEntity)

		return fmt.Errorf("failed to parse request body: %w", err)
	}

	message := bytes.NewBuffer([]byte{})

	if err := tpl.Execute(message, notification); err != nil {
		ctx.SetStatusCode(fasthttp.StatusInternalServerError)

		return fmt.Errorf("failed to render telegram template: %w", err)
	}

	if message.String() == "" {
		ctx.SetStatusCode(fasthttp.StatusBadRequest)

		return fmt.Errorf("rendered template is empty")
	}

	log.WithPrefix("fasthttpHandle").With("message", message.String()).Debug("rendered message")

	sendMessageURI := url.URL{
		Scheme: "https",
		Host:   "api.telegram.org",
		Path:   fmt.Sprintf("bot%s/sendMessage", os.Getenv("TELEGRAM_BOT_TOKEN")),
		RawQuery: url.Values{
			"chat_id": []string{
				os.Getenv("TELEGRAM_CHAT_ID"),
			},
			"text": []string{
				message.String(),
			},
			"parse_mode": []string{
				"HTML",
			},
		}.Encode(),
	}

	sendMessageReq, err := http.NewRequestWithContext(ctx, http.MethodGet, sendMessageURI.String(), http.NoBody)

	if err != nil {
		ctx.SetStatusCode(fasthttp.StatusBadGateway)

		return fmt.Errorf("failed to create request: %w", err)
	}

	sendMessageResp, err := http.DefaultClient.Do(sendMessageReq)

	if err != nil {
		ctx.SetStatusCode(fasthttp.StatusBadGateway)

		return fmt.Errorf("failed to send message: %w", err)
	}

	defer sendMessageResp.Body.Close()

	var sendMessageResponse dto.TgResponse

	if err := json.NewDecoder(sendMessageResp.Body).Decode(&sendMessageResponse); err != nil {
		ctx.SetStatusCode(fasthttp.StatusInternalServerError)

		return fmt.Errorf("failed to parse response: %w", err)
	}

	if !sendMessageResponse.OK {
		ctx.SetStatusCode(fasthttp.StatusBadRequest)

		return fmt.Errorf("failed to send message: %s", *sendMessageResponse.Description)
	}

	ctx.SetStatusCode(http.StatusOK)

	if err := json.NewEncoder(ctx).Encode(map[string]string{
		"status": "OK",
	}); err != nil {
		ctx.SetStatusCode(fasthttp.StatusInternalServerError)

		return fmt.Errorf("failed to encode response: %w", err)
	}

	return nil
}
