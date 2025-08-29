package model

import (
	"net/url"
	"time"
)

// @see https://datatracker.ietf.org/doc/html/draft-inadarei-api-health-check

const HealthMimeType = "application/health+json"

type Health struct {
	Status      HealthStatus              `json:"status"`
	Version     string                    `json:"version"`
	ReleaseID   string                    `json:"releaseId"`
	Notes       []string                  `json:"notes"`
	Output      string                    `json:"output"`
	ServiceID   string                    `json:"serviceId"`
	Description string                    `json:"description"`
	Checks      map[string][]*HealthCheck `json:"checks"`
	Links       map[string]*url.URL       `json:"links"`
}

type HealthStatus string

const (
	HealthStatusPass HealthStatus = "pass"
	HealthStatusFail              = "fail"
	HealthStatusWarn              = "warn"
)

type HealthCheck struct {
	ComponentID       string       `json:"componentId"`
	ComponentType     string       `json:"componentType"`
	ObservedValue     int          `json:"observedValue"`
	ObservedUnit      string       `json:"observedUnit"`
	Status            HealthStatus `json:"status"`
	AffectedEndpoints []*url.URL   `json:"affectedEndpoints"`
	Time              time.Time    `json:"time"`
	Output            string       `json:"output"`
}
