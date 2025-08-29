package main

import (
	"context"
	"fmt"
	"html/template"
	"io/fs"
	"net/http"
	"os"
	"path/filepath"
	"strings"

	"git.i-sphere.ru/isphere-services/login/contract"
	"git.i-sphere.ru/isphere-services/login/model"
	"git.i-sphere.ru/isphere-services/login/util"
	"github.com/gin-contrib/sessions"
	"github.com/gin-contrib/sessions/cookie"
	"github.com/gin-gonic/gin"
	"github.com/opentracing-contrib/go-gin/ginhttp"
	"github.com/opentracing/opentracing-go"
	"github.com/utrack/gin-csrf"
)

const TemplatesDir = "templates/"

func NewRouter(controllers []contract.Controller, ui *util.UI) (*gin.Engine, error) {
	router := gin.New()
	router.Use(ginhttp.Middleware(opentracing.GlobalTracer()))

	store := cookie.NewStore([]byte(os.Getenv("APP_SECRET")))

	router.Use(sessions.Sessions(model.SessionKey, store))
	router.Use(csrf.Middleware(csrf.Options{
		Secret: os.Getenv("APP_SECRET"),
		ErrorFunc: func(ctx *gin.Context) {
			ctx.JSON(http.StatusBadRequest, gin.H{
				"errors": []string{"CSRF token mismatch"},
			})

			ctx.AbortWithStatus(http.StatusBadRequest)
		},
	}))

	router.Use(func(ctx *gin.Context) {
		nativeContext := ctx.Request.Context()
		if nativeContext == nil {
			nativeContext = context.Background()
		}

		nativeContext = context.WithValue(nativeContext, "gin", ctx)

		ctx.Request = ctx.Request.WithContext(nativeContext)
	})

	router.Use(func(ctx *gin.Context) {
		ctx.Next()

		violations := map[string][]string{
			"errors": make([]string, 0),
		}

		for _, err := range ctx.Errors {
			violations["errors"] = append(violations["errors"], err.Error())
		}

		if len(violations["violations"]) > 0 {
			ctx.JSON(ctx.Writer.Status(), violations)
		}
	})

	functions := template.FuncMap{
		"DangerouslyImage":         ui.DangerouslyImage,
		"Dict":                     ui.Dict,
		"FilterNodesByGroups":      ui.FilterNodesByGroups,
		"GetNodeID":                ui.GetNodeID,
		"GetNodeInputType":         ui.GetNodeInputType,
		"GetNodeLabel":             ui.GetNodeLabel,
		"IsDebug":                  ui.IsDebug,
		"IsUINodeAnchorAttributes": ui.IsUINodeAnchorAttributes,
		"IsUINodeImageAttributes":  ui.IsUINodeImageAttributes,
		"IsUINodeInputAttributes":  ui.IsUINodeInputAttributes,
		"IsUINodeScriptAttributes": ui.IsUINodeScriptAttributes,
		"IsUINodeTextAttributes":   ui.IsUINodeTextAttributes,
	}

	if os.Getenv("APP_ENV") == "dev" {
		functions["Dump"] = ui.Dump
	}

	templates, err := loadTemplates(functions)
	if err != nil {
		return nil, fmt.Errorf("failed to load templates: %w", err)
	}

	router.SetHTMLTemplate(templates)

	for _, controller := range controllers {
		controller.Describe(router)
	}

	return router, nil
}

func loadTemplates(funcMap template.FuncMap) (*template.Template, error) {
	templates := template.New("")
	templates.Funcs(funcMap)

	if err := filepath.Walk(TemplatesDir, func(path string, info fs.FileInfo, err error) error {
		if err != nil {
			return fmt.Errorf("unexpected template walk error: %w", err)
		}

		if info.IsDir() || !strings.HasSuffix(path, ".template") {
			return nil
		}

		templateContent, err := os.ReadFile(path)
		if err != nil {
			return fmt.Errorf("failed to read file: %w", err)
		}

		templates, err = templates.New(filepath.Base(path)).Parse(string(templateContent))
		if err != nil {
			return fmt.Errorf("failed to parse template %s: %w", path, err)
		}

		return nil
	}); err != nil {
		return nil, fmt.Errorf("failed to scan %s: %w", TemplatesDir, err)
	}

	return templates, nil
}
