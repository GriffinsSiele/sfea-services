package functional

import (
	"context"
	"flag"
	"os"
	"path/filepath"
	"testing"
	"time"

	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/command"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/console"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/contract"
	"github.com/stretchr/testify/assert"
	"github.com/urfave/cli/v2"
	"go.uber.org/fx"
)

func TestDNS(t *testing.T) {
	wd, err := os.Getwd()
	assert.NoError(t, err)
	assert.NoError(t, os.Chdir(filepath.Dir(filepath.Dir(wd))))

	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()

	ctx = context.WithValue(ctx, contract.PredefinedValues, map[string]any{
		"ip":        "188.43.7.73",
		"starttime": time.Now().Unix(),
		"timeout":   -10,
	})

	fx.New(internal.Module(),
		fx.Invoke(func(application *console.Application, invoke *command.InvokeCommand, shutdowner fx.Shutdowner) error {
			defer func() {
				assert.NoError(t, shutdowner.Shutdown())
			}()

			fs := flag.NewFlagSet(application.Name, 0)
			fs.String("scope", "dns", "")

			assert.NoError(t, invoke.Action(cli.NewContext(
				application.App,
				fs,
				&cli.Context{Context: ctx},
			)))

			return nil
		}),
	).Run()
}
