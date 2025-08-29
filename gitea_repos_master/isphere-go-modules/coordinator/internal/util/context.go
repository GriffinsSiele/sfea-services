package util

import (
	"context"
	"time"
)

const TrackerKey string = "_tracker"

type Event struct {
	Name      string         `json:"name" yaml:"name"`
	Payload   map[string]any `json:"payload" yaml:"payload"`
	CreatedAt time.Time      `json:"created_at" yaml:"created_at"`
}

type Tracker struct {
	Events []*Event
}

func WithTracker(ctx context.Context) context.Context {
	tracker := &Tracker{}

	return context.WithValue(ctx, TrackerKey, tracker)
}

func TrackEvent(ctx context.Context, event *Event) {
	tracker, ok := ctx.Value(TrackerKey).(*Tracker)
	if !ok {
		return
	}

	event.CreatedAt = time.Now()
	tracker.Events = append(tracker.Events, event)
}

func GetTrackEvents(ctx context.Context) []*Event {
	tracker, ok := ctx.Value(TrackerKey).(*Tracker)
	if !ok {
		return nil
	}

	return tracker.Events
}
