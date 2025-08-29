package logrus

import (
	"bytes"
	"encoding/json"
	"fmt"
	"os"
	"os/user"
	"strings"
	"time"

	"github.com/fatih/color"
	"github.com/sirupsen/logrus"
)

type ISphereFormatter struct{}

func (t *ISphereFormatter) Format(entry *logrus.Entry) ([]byte, error) {
	sb := strings.Builder{}

	// timestamp
	sb.WriteString(entry.Time.Format(time.RFC3339))
	sb.WriteString(" - ")

	// username
	usr, err := user.Current()
	if err != nil {
		return nil, fmt.Errorf("failed to get current username: %w", err)
	}

	sb.WriteString(usr.Username)
	sb.WriteString(" - ")

	// level
	sb.WriteString(fmt.Sprintf("[%s]", strings.ToUpper(entry.Level.String())))
	sb.WriteString(" - ")

	// caller
	if caller := entry.Caller; caller != nil {
		// caller:filename
		filename := caller.File

		projectDir, err := os.Getwd()
		if err != nil {
			return nil, fmt.Errorf("failed to get working directory: %w", err)
		}

		filename = strings.TrimPrefix(strings.TrimPrefix(filename, projectDir), "/")

		sb.WriteString(fmt.Sprintf("(%s)", filename))

		// caller:method
		functionNamespace := strings.Split(caller.Function, "/")

		if len(functionNamespace) > 0 {
			var (
				function    = functionNamespace[len(functionNamespace)-1]
				functionStl = strings.SplitN(function, ".", 2)
			)

			sb.WriteString(functionStl[len(functionStl)-1])
		}

		// caller:line
		sb.WriteString(fmt.Sprintf("(%d)", caller.Line))
		sb.WriteString(" - ")
	}

	// message
	sb.WriteString(entry.Message)

	if len(entry.Data) > 0 {
		for k, v := range entry.Data {
			switch err := v.(type) {
			case error:
				entry.Data[k] = fmt.Sprintf("%v", err)
			}
		}

		serialized, err := json.Marshal(entry.Data)
		if err != nil {
			return nil, fmt.Errorf("failed to marshal message data: %w", err)
		}

		sb.WriteString(fmt.Sprintf(": %s", string(serialized)))
	}

	buf := bytes.NewBuffer([]byte{})

	var styles []color.Attribute

	switch entry.Level {
	case logrus.PanicLevel, logrus.FatalLevel:
		styles = append(styles, color.BgRed, color.FgWhite, color.BlinkSlow)
	case logrus.ErrorLevel:
		styles = append(styles, color.FgRed)
	case logrus.WarnLevel:
		styles = append(styles, color.FgYellow)
	case logrus.InfoLevel:
	case logrus.DebugLevel, logrus.TraceLevel:
		styles = append(styles, color.Faint)
	}

	if _, err = color.New(styles...).Fprintln(buf, sb.String()); err != nil {
		return nil, fmt.Errorf("failed to write colored buffer: %w", err)
	}

	return buf.Bytes(), nil
}
