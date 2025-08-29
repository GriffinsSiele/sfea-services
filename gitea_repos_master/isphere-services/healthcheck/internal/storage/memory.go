package storage

import (
	"context"
	"sync"
	"time"
)

type Memory struct {
	storage []*Events
	rw      sync.RWMutex
}

func NewMemory() *Memory {
	return &Memory{}
}

// Listen removes old logs
func (m *Memory) Listen(ctx context.Context) {
	for {
		select {
		case <-ctx.Done():
			return
		case <-time.After(time.Minute):
			m.rw.Lock()
			for i := range m.storage {
				if m.storage[i].CreatedAt.Before(time.Now().Add(-30 * time.Minute)) {
					m.storage = append(m.storage[:i], m.storage[i+1:]...)
				}
			}
			m.rw.Unlock()
		}
	}
}

func (m *Memory) Store(events *Events) {
	m.rw.Lock()
	defer m.rw.Unlock()
	m.storage = append(m.storage, events)
}

func (m *Memory) Load() []*Events {
	m.rw.RLock()
	defer m.rw.RUnlock()
	return m.storage
}

type MemoryLog struct {
	ID        string        `json:"id" yaml:"id"`
	Name      string        `json:"name" yaml:"name"`
	NodeName  string        `json:"node_name" yaml:"node_name"`
	Hostname  string        `json:"hostname" yaml:"hostname"`
	Events    []*Event      `json:"events,omitempty" yaml:"events,omitempty"`
	Error     string        `json:"error,omitempty" yaml:"error,omitempty"`
	Duration  time.Duration `json:"duration,omitempty" yaml:"duration,omitempty"`
	CreatedAt time.Time     `json:"created_at" yaml:"created_at"`
}
