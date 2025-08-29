package command

import (
	"context"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"net/url"
	"regexp"
	"strings"
	"sync"

	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/config"
	"github.com/sirupsen/logrus"
	"github.com/urfave/cli/v2"
)

type CleanUpCommand struct {
	cfg *config.Config
}

func NewCleanUpCommand(cfg *config.Config) *CleanUpCommand {
	return &CleanUpCommand{
		cfg: cfg,
	}
}

func (t *CleanUpCommand) Describe() *cli.Command {
	return &cli.Command{
		Category: "cleanup",
		Name:     "cleanup",
		Action:   t.Action,
		Flags: cli.FlagsByName{
			&cli.StringFlag{
				Name: "pattern",
			},
			&cli.BoolFlag{
				Name:  "exchanges",
				Value: false,
			},
		},
	}
}

func (t *CleanUpCommand) Action(c *cli.Context) error {
	pattern := regexp.MustCompile(c.String("pattern"))

	subject := "queues"
	if c.Bool("exchanges") {
		subject = "exchanges"
	}

	dsn := buildDSN(t.cfg.Services.RabbitMQ, subject)
	req, err := buildRequest(c.Context, dsn)
	if err != nil {
		return fmt.Errorf("failed to create request: %w", err)
	}

	client := http.DefaultClient
	resp, err := client.Do(req)
	if err != nil {
		return fmt.Errorf("failed to do request: %w", err)
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return fmt.Errorf("unexpected status code: %d", resp.StatusCode)
	}

	queues, err := decodeResponse(resp.Body)
	if err != nil {
		return fmt.Errorf("failed to decode response: %w", err)
	}

	filtered := filterQueues(queues, pattern)

	var wg sync.WaitGroup
	wg.Add(len(filtered))

	for _, queue := range filtered {
		go func(queue *Queue) {
			defer wg.Done()

			deleteReq, err := http.NewRequestWithContext(c.Context, http.MethodDelete, dsn+url.QueryEscape(queue.VirtualHost)+"/"+queue.Name, http.NoBody)
			if err != nil {
				logrus.WithError(err).Errorf("failed to create request: %v", err)
				return
			}

			if _, err := client.Do(deleteReq); err != nil {
				logrus.WithError(err).Errorf("failed to do request: %v", err)
			}

			logrus.WithField("queue", queue).Info("queue/exchange was deleted")
		}(queue)
	}

	wg.Wait()

	return nil
}

func buildDSN(cfg config.RabbitMQService, subject string) string {
	dsn := url.URL{
		Scheme: "http",
		User:   url.UserPassword(cfg.Username, cfg.Password),
		Host:   strings.ReplaceAll(cfg.Addr, ":5672", ":15672"),
		Path:   fmt.Sprintf("/api/%s/", subject),
	}
	return dsn.String()
}

func buildRequest(ctx context.Context, dsn string) (*http.Request, error) {
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, dsn, http.NoBody)
	if err != nil {
		return nil, fmt.Errorf("failed to create request: %w", err)
	}
	return req, nil
}

func decodeResponse(body io.Reader) ([]*Queue, error) {
	var queues []*Queue
	err := json.NewDecoder(body).Decode(&queues)
	return queues, err
}

func filterQueues(queues []*Queue, pattern *regexp.Regexp) []*Queue {
	filtered := make([]*Queue, 0, len(queues))
	for _, q := range queues {
		if pattern.MatchString(q.Name) {
			filtered = append(filtered, q)
		}
	}
	return filtered
}

type Queue struct {
	Name        string `json:"name"`
	VirtualHost string `json:"vhost"`
}
