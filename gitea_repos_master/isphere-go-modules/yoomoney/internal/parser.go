package internal

import (
	"bytes"
	"fmt"
	"io"
	"math/rand"
	"net/http"
	"os"
	"strconv"
	"strings"
	"sync"
	"time"

	"github.com/corpix/uarand"
	"github.com/google/uuid"
	"github.com/sirupsen/logrus"
	"go.opentelemetry.io/otel/trace"
	"golang.org/x/net/context"
)

type Parser struct {
	client *http.Client
	tracer trace.Tracer

	activeSessions []*Session
	rw             *sync.RWMutex
}

func NewParser(client *http.Client, tracer trace.Tracer) *Parser {
	return &Parser{
		client: client,
		tracer: tracer,
		rw:     new(sync.RWMutex),
	}
}

func (t *Parser) StartSessionCreation(ctx context.Context) {
	if err := t.handleSessionCreation(ctx); err != nil {
		logrus.WithContext(ctx).WithError(err).Fatal("failed to handle session initialization")
	}

	for {
		select {
		case <-ctx.Done():
			return
		case <-time.After(5 * time.Second):
			if err := t.handleSessionCreation(ctx); err != nil {
				logrus.WithContext(ctx).WithError(err).Fatal("failed to handle session iteration")
			}
		}
	}
}

func (t *Parser) GetSession(ctx context.Context, requestID string) (*Session, error) {
	t.rw.RLock()
	defer t.rw.RUnlock()

	if len(t.activeSessions) == 0 {
		return nil, fmt.Errorf("no active sessions found, could not complete request")
	}

	return t.activeSessions[rand.Intn(len(t.activeSessions))], nil
}

func (t *Parser) handleSessionCreation(ctx context.Context) error {
	maxSessions, err := strconv.Atoi(os.Getenv("MAX_SESSIONS"))
	if err != nil {
		return fmt.Errorf("failed to parse max sessions: %w", err)
	}

	needsQuota := maxSessions - len(t.activeSessions)
	if needsQuota == 0 {
		return nil
	}

	maxSessionsPerIteration, err := strconv.Atoi(os.Getenv("MAX_SESSIONS_PER_ITERATION"))
	if err != nil {
		return fmt.Errorf("failed to parse max sessions per iteration: %w", err)
	}

	logrus.WithContext(ctx).WithField("needs_quota", needsQuota).Info("needs for new sessions")

	t.rw.Lock()
	defer t.rw.Unlock()

	var wg sync.WaitGroup
	var j int

	for i := 0; i < needsQuota; i++ {
		wg.Add(1)
		go func(ctx context.Context, i int) {
			defer wg.Done()
			if j >= maxSessionsPerIteration {
				return
			}

			session, err := t.getSession(ctx, "")
			if err != nil {
				logrus.WithContext(ctx).WithError(err).Error("cannot get session")
			}

			t.activeSessions = append(t.activeSessions, session)
			j++
		}(ctx, i)
	}

	wg.Wait() // if not all sessions have been created, it's ok - leave, it finished at the future iteration
	return nil
}

func (t *Parser) DiscretizeSession(ctx context.Context, session *Session) {
	logrus.WithContext(ctx).WithField("id", session.ID).Info("sessions was discredited and deleted from memory")

	t.rw.Lock()
	defer t.rw.Unlock()

	for i, s := range t.activeSessions {
		if s.ID == session.ID {
			t.activeSessions = append(t.activeSessions[:i], t.activeSessions[i+1:]...)
			break
		}
	}
}

func (t *Parser) getSession(ctx context.Context, requestID string) (*Session, error) {
	ctx, span := t.tracer.Start(ctx, "get secret key")
	defer span.End()

	req, err := t.createRequest(ctx, "https://yoomoney.ru/transfer", requestID)
	if err != nil {
		return nil, fmt.Errorf("failed to create request: %w", err)
	}

	req.Header.Set("X-Sphere-Proxy-Spec-Group-Id", "5")
	req.Header.Set("X-Sphere-Proxy-Spec-Country-Code", "ru")

	logrus.WithContext(ctx).WithField("request", req).Info("create request")

	resp, err := t.client.Do(req)
	if err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to send request")
		return nil, fmt.Errorf("failed to send request: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	proxyID, err := strconv.Atoi(resp.Header.Get("X-Sphere-Proxy-Spec-Id"))
	if err != nil {
		logrus.WithContext(ctx).WithError(err).Error("failed to parse X-Sphere-Proxy-Spec-Id")
		return nil, fmt.Errorf("failed to parse X-Sphere-Proxy-Spec-Id: %w", err)
	}

	bodyBytes, err := io.ReadAll(resp.Body)
	if err != nil {
		return nil, fmt.Errorf("failed to read response body: %w", err)
	}

	secretKey := t.extractSecretKey(bodyBytes)
	if secretKey == "" {
		return nil, fmt.Errorf("failed to find secret key")
	}

	return &Session{
		ID:      uuid.New(),
		ProxyID: proxyID,
		SecretKey: SecretKey{
			UserAgent: req.Header.Get("User-Agent"),
			SecretKey: secretKey,
		},
	}, nil
}

func (t *Parser) createRequest(ctx context.Context, url, requestID string) (*http.Request, error) {
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, url, http.NoBody)
	if err != nil {
		return nil, fmt.Errorf("failed to create request: %w", err)
	}

	userAgent := uarand.GetRandom()
	req.Header.Set("User-Agent", userAgent)
	req.Header.Set("Accept", "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9")
	req.Header.Set("Accept-Language", "ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7")
	req.Header.Set("Cache-Control", "max-age=0")
	req.Header.Set("Connection", "keep-alive")
	req.Header.Set("Upgrade-Insecure-Requests", "1")
	req.Header.Set("Sec-Fetch-Dest", "document")
	req.Header.Set("Sec-Fetch-Mode", "navigate")
	req.Header.Set("Sec-Fetch-Site", "same-origin")
	req.Header.Set("Sec-Fetch-User", "?1")
	req.Header.Set("Sec-GPC", "1")
	req.Header.Set("Pragma", "no-cache")
	req.Header.Set("Referer", "https://yoomoney.ru/transfer")
	if requestID != "" {
		req.Header.Set("X-Request-Id", requestID)
	}
	req.Header.Set("X-Sphere-Proxy-Spec-Country-Code", "ru")
	req.Header.Set("X-Sphere-Proxy-Spec-Group-Id", "5")

	return req, nil
}

func (t *Parser) extractSecretKey(bodyBytes []byte) string {
	pos := bytes.Index(bodyBytes, []byte(`"secretKey"`))
	if pos == -1 {
		return ""
	}

	var foundColon bool
	var firstQuoteFound bool
	var secretKeyBuilder strings.Builder
	for i := pos; i < len(bodyBytes); i++ {
		if !foundColon {
			if bodyBytes[i] == ':' {
				foundColon = true
			}
			continue
		}
		if !firstQuoteFound {
			if bodyBytes[i] == '"' {
				firstQuoteFound = true
			}
			continue
		}
		if bodyBytes[i] == '"' {
			break
		}
		secretKeyBuilder.WriteByte(bodyBytes[i])
	}

	return secretKeyBuilder.String()
}

type Session struct {
	ID        uuid.UUID
	ProxyID   int
	SecretKey SecretKey
	InUse     bool
}

type SecretKey struct {
	UserAgent string
	SecretKey string
}
