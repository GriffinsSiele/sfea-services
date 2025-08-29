package storage

import (
	"time"

	"github.com/google/uuid"
)

type Events struct {
	RequestID *uuid.UUID    `json:"-"`
	Name      string        `json:"name"`
	NodeName  string        `json:"node_name"`
	Hostname  string        `json:"hostname"`
	Events    []*Event      `json:"events"`
	CreatedAt time.Time     `json:"created_at"`
	Duration  time.Duration `json:"duration"`
	Error     string        `json:"error,omitempty"`
}

func NewEvents(name, nodeName, hostname string) *Events {
	return &Events{
		Name:      name,
		NodeName:  nodeName,
		Hostname:  hostname,
		Events:    make([]*Event, 0),
		CreatedAt: time.Now(),
	}
}

func (e *Events) Append(event *Event) {
	e.Events = append(e.Events, event)
}

type Event struct {
	Name      string         `json:"name" yaml:"name"`
	Subject   string         `json:"subject" yaml:"subject"`
	Opaque    map[string]any `json:"opaque" yaml:"opaque"`
	Error     string         `json:"error,omitempty" yaml:"error,omitempty"`
	Duration  string         `json:"duration,omitempty" yaml:"duration,omitempty"`
	CreatedAt time.Time      `json:"created_at" yaml:"created_at"`
}

func NewEvent(name, subject string) *Event {
	return &Event{
		Name:      name,
		Subject:   subject,
		Opaque:    map[string]any{},
		CreatedAt: time.Now(),
	}
}

func (e *Event) With(k string, v any) *Event {
	e.Opaque[k] = v
	return e
}

func (e *Event) WithDuration(d time.Duration) *Event {
	e.Duration = d.String()
	return e
}

func (e *Event) WithError(err error) *Event {
	if err != nil {
		e.Error = err.Error()
	}
	return e
}
