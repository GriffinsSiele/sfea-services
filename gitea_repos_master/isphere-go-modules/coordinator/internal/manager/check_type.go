package manager

import (
	"context"
	"fmt"
	"io"
	"time"

	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/clients/clickhouse"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/clients/keydb"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/config"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/model"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/repository"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/tracing"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/util"
	"github.com/opentracing/opentracing-go"
	"github.com/opentracing/opentracing-go/log"
)

type CheckTypeManager struct {
	cfg              *config.Config
	clickhouseClient *clickhouse.Client
	keyDBClient      *keydb.Client
	providerManager  *ProviderManager
	providerRepo     *repository.ProviderRepository
}

func NewCheckTypeManager(
	cfg *config.Config,
	clickhouseClient *clickhouse.Client,
	keyDBClient *keydb.Client,
	providerManager *ProviderManager,
	providerRepo *repository.ProviderRepository,
) *CheckTypeManager {
	return &CheckTypeManager{
		cfg:              cfg,
		clickhouseClient: clickhouseClient,
		keyDBClient:      keyDBClient,
		providerManager:  providerManager,
		providerRepo:     providerRepo,
	}
}

func (t *CheckTypeManager) Apply(ctx context.Context, checkType *config.CheckType, requestReader io.Reader) (*model.Response, error) {

	applyLogOptions := &model.ApplyLogOptions{
		Scope:     checkType.Scope(),
		StartTime: time.Now(),
	}

	//defer func(opts *model.ApplyLogOptions) {
	//	conn, err := t.clickhouseClient.Acquire(ctx)
	//	if err != nil {
	//		logrus.WithContext(ctx).WithError(err).Error("failed to acquire clickhouse connection")
	//		return
	//	}
	//	defer conn.Release()
	//
	//	opts.EndTime = util.Ptr(time.Now())
	//	conn.PushApplyLogOptions(ctx, opts)
	//}(applyLogOptions)

	tracer, closer := tracing.MustTracerCloser()
	defer tracing.MustClose(closer)

	ctx, span := tracing.StartSpanWithContext(ctx, tracer, "apply on the check type manager type")
	defer span.Finish()

	module, err := t.findModuleByUpstreamProvider(ctx, tracer, checkType)
	if err != nil {
		applyLogOptions.Error = err
		return nil, fmt.Errorf("find module by upstream provider: %w", util.Fail(span, err))
	}

	response, err := t.apply(ctx, tracer, checkType, module, requestReader)
	if err != nil {
		applyLogOptions.Error = err
		return nil, fmt.Errorf("apply: %w", util.Fail(span, err))
	}

	applyLogOptions.StatusCode = response.Code
	return response, nil
}

func (t *CheckTypeManager) findModuleByUpstreamProvider(parentCtx context.Context, tracer opentracing.Tracer, checkType *config.CheckType) (*config.Provider, error) {
	ctx, span := tracing.StartSpanWithContext(parentCtx, tracer, "find module by upstream provider")
	defer span.Finish()

	module, err := t.providerRepo.Find(ctx, checkType.Upstream.Provider)
	if err != nil {
		return nil, fmt.Errorf("find module: %w", util.Fail(span, err))
	}
	span.LogFields(log.Object("module", module))

	return module, nil
}

func (t *CheckTypeManager) apply(parentCtx context.Context, tracer opentracing.Tracer, checkType *config.CheckType, module *config.Provider, requestReader io.Reader) (*model.Response, error) {
	ctx, span := tracing.StartSpanWithContext(parentCtx, tracer, "apply module")
	defer span.Finish()

	response, err := t.providerManager.Apply(ctx, checkType, module, requestReader)
	if err != nil {
		return nil, fmt.Errorf("apply module: %w", util.Fail(span, err))
	}
	span.LogFields(log.Object("response", response))

	return response, nil
}
