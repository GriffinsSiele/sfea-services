package model

import "time"

type ApplyLogOptions struct {
	Scope      string
	StatusCode int
	Error      error
	StartTime  time.Time
	EndTime    *time.Time
}
