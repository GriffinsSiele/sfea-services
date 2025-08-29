package main

import (
	"Golang/controller"
	"Golang/event_listener"
	"github.com/gorilla/mux"
	"github.com/sirupsen/logrus"
	"net/http"
)

type App struct {
	controllers                []controller.Controller
	requestLoggerEventListener *event_listener.RequestLoggerEventListener

	port string
}

func (t *App) Run() error {
	router := mux.NewRouter()
	router.Use(t.requestLoggerEventListener.Invoke)
	for _, ctrl := range t.controllers {
		router.HandleFunc(ctrl.GetPath(), ctrl.Invoke).Methods(ctrl.GetMethods()...)
	}
	dsn := ":" + t.port
	logrus.Infof("The proxy server is running on %s", dsn)
	return http.ListenAndServe(dsn, router)
}

func NewApp(
	controllers []controller.Controller,
	requestLoggerEventListener *event_listener.RequestLoggerEventListener,

	port string,
) *App {
	return &App{
		controllers:                controllers,
		requestLoggerEventListener: requestLoggerEventListener,

		port: port,
	}
}
