package controller

import (
	"net/http"

	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/config"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/hydrator"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/util"
	"github.com/gin-gonic/gin"
	"github.com/sirupsen/logrus"
)

type CheckTypesController struct {
	cfg      *config.Config
	hydrator *hydrator.CheckType
}

func NewCheckTypesController(cfg *config.Config, hydrator *hydrator.CheckType) *CheckTypesController {
	return &CheckTypesController{
		cfg:      cfg,
		hydrator: hydrator,
	}
}

func (t *CheckTypesController) Describe(router *gin.Engine) {
	router.GET("/api/v1/check-types", t.GET)
}

func (t *CheckTypesController) GET(c *gin.Context) {
	items := make([]*hydrator.Item, 0, len(t.cfg.CheckTypes))

	for name, checkType := range t.cfg.CheckTypes {
		if !checkType.Enabled {
			continue
		}

		item, err := t.hydrator.Hydrate(name, checkType, c.Request)
		if err != nil {
			logrus.WithError(err).Error("failed to hydrate check type")
			continue
		}

		items = append(items, item)
	}

	util.Marshal(c, http.StatusOK, items)
}
