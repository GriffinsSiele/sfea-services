package controller

import (
	"context"
	"net/http"
	"strconv"
	"strings"
	"time"

	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/contract"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/hydrator"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/manager"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/model"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/repository"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/util"
	"github.com/gin-gonic/gin"
	"github.com/google/uuid"
)

type CheckTypeController struct {
	checkTypeManager *manager.CheckTypeManager
	checkTypeRepo    *repository.CheckTypeRepository
	hydrator         *hydrator.CheckType
}

func NewCheckTypeController(
	checkTypeManager *manager.CheckTypeManager,
	checkTypeRepo *repository.CheckTypeRepository,
	hydrator *hydrator.CheckType,
) *CheckTypeController {
	return &CheckTypeController{
		checkTypeManager: checkTypeManager,
		checkTypeRepo:    checkTypeRepo,
		hydrator:         hydrator,
	}
}

func (t *CheckTypeController) Describe(router *gin.Engine) {
	router.GET("/api/v1/check-types/:name", t.GET)
	router.POST("/api/v1/check-types/:name", t.POST)
}

func (t *CheckTypeController) GET(c *gin.Context) {
	ctx := context.Context(c)
	checkType, err := t.checkTypeRepo.Find(ctx, c.Param("name"))
	if err != nil {
		c.AbortWithStatusJSON(http.StatusNotFound, model.NewError(err))
		return
	}

	item, err := t.hydrator.Hydrate(c.Param("name"), checkType, c.Request)
	if err != nil {
		c.AbortWithStatusJSON(http.StatusInternalServerError, model.NewError(err))
		return
	}

	util.Marshal(c, http.StatusOK, item)
}

func (t *CheckTypeController) POST(c *gin.Context) {
	//goland:noinspection GoUnhandledErrorResult
	defer c.Request.Body.Close()

	ctx := context.Context(c)
	checkType, err := t.checkTypeRepo.Find(ctx, c.Param("name"))
	if err != nil {
		c.AbortWithStatusJSON(http.StatusNotFound, model.NewError(err))
		return
	}

	xRequestID := c.GetHeader(contract.ExtraRequestID)
	if xRequestID != "" {
		c.Writer.Header().Add("Vary", contract.ExtraRequestID)
	} else {
		xRequestID = uuid.NewString()
	}
	c.Header(contract.ExtraRequestID, xRequestID)

	ctx = context.WithValue(ctx, contract.ExtraRequestIDCtxValue, xRequestID)
	if strings.Contains(c.GetHeader(contract.CacheControl), string(contract.CacheControlNoCache)) {
		t.addCacheControlVary(c)
		ctx = context.WithValue(ctx, contract.CacheControlNoCache, new(any))
	}

	if strings.Contains(c.GetHeader(contract.CacheControl), string(contract.CacheControlNoStore)) {
		t.addCacheControlVary(c)
		ctx = context.WithValue(ctx, contract.CacheControlNoStore, new(any))
	}

	if strings.Contains(c.GetHeader(contract.CacheControl), string(contract.CacheControlOnlyIfCached)) {
		t.addCacheControlVary(c)
		ctx = context.WithValue(ctx, contract.CacheControlOnlyIfCached, new(any))
	}

	if contract.MatchCacheControlMaxAge.MatchString(c.GetHeader(contract.CacheControl)) {
		maxAge, err := strconv.Atoi(contract.MatchCacheControlMaxAge.FindString(c.GetHeader(contract.CacheControl)))
		if err != nil {
			c.AbortWithStatusJSON(http.StatusBadRequest, model.NewError(err))
			return
		}
		t.addCacheControlVary(c)
		ctx = context.WithValue(ctx, contract.CacheControlMaxAge, maxAge)
	}

	response, err := t.checkTypeManager.Apply(ctx, checkType, c.Request.Body)
	if err != nil {
		c.AbortWithStatusJSON(http.StatusUnprocessableEntity, model.NewError(err))
		return
	}

	if ttl := response.Metadata.TTL; ttl != nil {
		if ttl.Age != nil {
			c.Writer.Header().Add("Age", strconv.Itoa(util.PtrVal(ttl.Age)))
		}
		if ttl.LastModified != nil {
			c.Writer.Header().Add("Last-Modified", util.PtrVal(ttl.LastModified).Format(time.RFC1123))
		}
		if ttl.ETag != nil {
			c.Writer.Header().Add("ETag", util.PtrVal(ttl.ETag))
		}
		if ttl.Expires != nil {
			c.Writer.Header().Add("Expires", util.PtrVal(ttl.Expires).Format(time.RFC1123))
		}
	}

	util.Marshal(c, http.StatusOK, response)
}

func (t *CheckTypeController) addCacheControlVary(c *gin.Context) {
	values := c.Writer.Header().Values("Vary")

	if !util.SliceContains(values, contract.CacheControl) {
		c.Writer.Header().Add("Vary", contract.CacheControl)
	}
}
