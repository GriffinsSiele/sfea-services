package internal

import (
	"context"
	"math/rand"
	"os"
	"strconv"
	"sync"
	"time"

	"github.com/pkg/errors"
	"github.com/sirupsen/logrus"
)

type SessionWatcher struct {
	sessionManager *SessionManager

	defaultDuration   time.Duration
	availableSessions []*Session
	maxSessions       int
	rw                sync.RWMutex
}

func NewSessionWatcher(sessionManager *SessionManager) (*SessionWatcher, error) {
	maxSessions, err := strconv.Atoi(os.Getenv("MAX_SESSIONS"))
	if err != nil {
		return nil, errors.Wrap(err, "failed to parse max sessions")
	}

	return &SessionWatcher{
		sessionManager: sessionManager,

		defaultDuration: 1 * time.Second,
		maxSessions:     maxSessions,
	}, nil
}

func (s *SessionWatcher) Watch(ctx context.Context) error {
	if err := s.handle(ctx); err != nil {
		return err
	}

	for {
		select {
		case <-ctx.Done():
			return ctx.Err()
		case <-time.After(s.defaultDuration):
			if err := s.handle(ctx); err != nil {
				return err
			}
		}
	}
}

func (s *SessionWatcher) One(ctx context.Context, ch chan *Session) {
	if s.canSelectSession() {
		ch <- s.selectSession()
		return
	}

	for {
		select {
		case <-ctx.Done():
			return
		case <-time.After(1 * time.Second):
			if s.canSelectSession() {
				ch <- s.selectSession()
				return
			}
		}
	}
}

func (s *SessionWatcher) Delete(ctx context.Context, session *Session) {
	s.rw.Lock()
	defer s.rw.Unlock()

	logrus.WithContext(ctx).WithField("session_id", session.ID).Warn("deleting broken session")

	for i, sess := range s.availableSessions {
		if sess.ID == session.ID {
			s.availableSessions = append(s.availableSessions[:], s.availableSessions[i+1:]...)
			return
		}
	}
}

func (s *SessionWatcher) Free(session *Session) {
	s.rw.Lock()
	defer s.rw.Unlock()

	session.InUse = false

	logrus.WithField("session_id", session.ID).Info("session is free")
}

func (s *SessionWatcher) canSelectSession() bool {
	s.rw.RLock()
	defer s.rw.RUnlock()

	freeSessions := make([]*Session, 0, len(s.availableSessions))
	for _, sess := range s.availableSessions {
		if !sess.InUse {
			freeSessions = append(freeSessions, sess)
		}
	}

	return len(freeSessions) > 0
}

func (s *SessionWatcher) selectSession() *Session {
	s.rw.Lock()
	defer s.rw.Unlock()

	sess := s.availableSessions[rand.Intn(len(s.availableSessions))]
	sess.InUse = true

	logrus.WithField("session_id", sess.ID).Info("selecting session")

	return sess
}

func (s *SessionWatcher) handle(ctx context.Context) error {
	needsQuota := s.needsQuota()
	if needsQuota < 1 {
		return nil
	}

	s.rw.Lock()
	defer s.rw.Unlock()

	var wg sync.WaitGroup
	for i := 0; i < needsQuota; i++ {
		wg.Add(1)
		go func(i int) {
			defer wg.Done()
			session, err := s.sessionManager.NewSession(ctx)
			if err != nil || session == nil {
				logrus.WithContext(ctx).WithError(err).Error("failed to create session")
				return
			}
			s.availableSessions = append(s.availableSessions, session)
			logrus.WithContext(ctx).WithField("session_id", session.ID).Info("created new session")
		}(i)
	}
	wg.Wait()

	return nil
}

func (s *SessionWatcher) needsQuota() int {
	s.rw.RLock()
	defer s.rw.RUnlock()

	return s.maxSessions - len(s.availableSessions)
}
