package pkg

import (
	"context"
	"math/rand"
	"os"
	"strconv"
	"sync"
	"time"

	"github.com/sirupsen/logrus"
	"i-sphere.ru/yoomoney/internal"
)

type SessionWatcher struct {
	sessionManager *SessionManager

	defaultDuration   time.Duration
	availableSessions []*internal.Session
	maxSessions       int
	rw                *sync.RWMutex
}

func NewSessionWatcher(sessionManager *SessionManager) (*SessionWatcher, error) {
	maxSessions, err := strconv.Atoi(os.Getenv("MAX_SESSIONS"))
	if err != nil {
		return nil, err
	}

	return &SessionWatcher{
		sessionManager: sessionManager,

		defaultDuration: 1 * time.Second,
		maxSessions:     maxSessions,
		rw:              new(sync.RWMutex),
	}, nil
}

func (w *SessionWatcher) Watch(ctx context.Context) error {
	if err := w.handle(ctx); err != nil {
		return err
	}

	for {
		select {
		case <-ctx.Done():
			return ctx.Err()
		case <-time.After(w.defaultDuration):
			if err := w.handle(ctx); err != nil {
				return err
			}
		}
	}
}

func (w *SessionWatcher) One(ctx context.Context, ch chan *internal.Session) {
	if w.canSelectSession() {
		ch <- w.selectSession()
		return
	}

	for {
		select {
		case <-ctx.Done():
			return
		case <-time.After(1 * time.Second):
			if w.canSelectSession() {
				ch <- w.selectSession()
				return
			}
		}
	}
}

func (w *SessionWatcher) Delete(ctx context.Context, session *internal.Session) {
	w.rw.Lock()
	defer w.rw.Unlock()

	logrus.WithContext(ctx).WithField("session_id", session.ID).Warn("deleting broken session")

	for i, s := range w.availableSessions {
		if s.ID == session.ID {
			w.availableSessions = append(w.availableSessions[:i], w.availableSessions[i+1:]...)
			return
		}
	}
}

func (w *SessionWatcher) canSelectSession() bool {
	w.rw.RLock()
	defer w.rw.RUnlock()

	freeSessions := make([]*internal.Session, 0, len(w.availableSessions))
	for _, s := range w.availableSessions {
		if !s.InUse {
			freeSessions = append(freeSessions, s)
		}
	}

	return len(freeSessions) > 0
}

func (w *SessionWatcher) selectSession() *internal.Session {
	w.rw.Lock()
	defer w.rw.Unlock()

	session := w.availableSessions[rand.Intn(len(w.availableSessions))]
	session.InUse = true

	logrus.WithField("session_id", session.ID).Info("selecting session")

	return session
}

func (w *SessionWatcher) Free(session *internal.Session) {
	w.rw.Lock()
	defer w.rw.Unlock()

	session.InUse = false

	logrus.WithField("session_id", session.ID).Info("session is free")
}

func (w *SessionWatcher) handle(ctx context.Context) error {
	needsQuota := w.maxSessions - len(w.availableSessions)
	if needsQuota < 1 {
		return nil
	}

	w.rw.Lock()
	defer w.rw.Unlock()

	var wg sync.WaitGroup
	for i := 0; i < needsQuota; i++ {
		wg.Add(1)
		go func(i int) {
			defer wg.Done()
			session, err := w.sessionManager.NewSession(ctx)
			if err != nil {
				logrus.WithContext(ctx).WithError(err).Error("failed to create new session")
				return
			}
			w.availableSessions = append(w.availableSessions, session)
			logrus.WithContext(ctx).WithField("session_id", session.ID).Info("created new session")
		}(i)
	}
	wg.Wait()

	return nil
}
