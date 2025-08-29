package shell

import (
	"context"

	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/config"
	"github.com/sirupsen/logrus"
)

type Pool struct{}

func NewPool() (*Pool, error) {
	return &Pool{}, nil
}

func (p *Pool) Acquire(ctx context.Context, _ *config.Provider) (*Conn, error) {
	logrus.WithContext(ctx).Debug("create shell connection")

	return &Conn{NewClient(p)}, nil
}
