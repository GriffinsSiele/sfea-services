package util

import (
	"os"

	"github.com/jedib0t/go-pretty/v6/progress"
)

func NewProgressWriter(trackers ...*progress.Tracker) progress.Writer {
	writer := progress.NewWriter()

	writer.SetOutputWriter(os.Stdout)
	writer.SetAutoStop(true)
	writer.SetTrackerPosition(progress.PositionRight)

	writer.Style().Visibility.ETA = true
	writer.Style().Visibility.Speed = true

	writer.AppendTrackers(trackers)

	return writer
}
