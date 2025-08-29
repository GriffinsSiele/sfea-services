package util

import (
	"context"
	"crypto/tls"
	"encoding/xml"
	"fmt"
	"io"
	"net/http"
	"os"
	"time"

	"github.com/corpix/uarand"
	"github.com/schollz/progressbar/v3"
	"github.com/sirupsen/logrus"
)

type Date struct {
	time.Time
}

func (t *Date) UnmarshalXMLAttr(attr xml.Attr) error {
	if attr.Value == "" {
		return nil
	}

	parsed, err := time.Parse("02.01.2006", attr.Value)
	if err != nil {
		return fmt.Errorf("failed to parse date attr: %w", err)
	}

	t.Time = parsed

	return nil
}

type Period struct {
	Years       int
	Months      int
	Days        int
	StringValue string
}

func (t *Period) String() string {
	return fmt.Sprintf("%d years %d months %d days", t.Years, t.Months, t.Days)
}

func Download(ctx context.Context, filename, link string) error {
	f, err := os.OpenFile(filename, os.O_CREATE|os.O_RDWR, 0o0644)
	if err != nil {
		return fmt.Errorf("failed to open file: %w", err)
	}

	//goland:noinspection GoUnhandledErrorResult
	defer f.Close()

	req, err := http.NewRequestWithContext(ctx, http.MethodGet, link, http.NoBody)
	if err != nil {
		return fmt.Errorf("failed to create request: %w", err)
	}

	if userAgent, ok := ctx.Value("user-agent").(string); ok {
		req.Header.Set("User-Agent", userAgent)
	} else {
		req.Header.Set("User-Agent", uarand.GetRandom())
	}

	client := &http.Client{
		Transport: &http.Transport{
			TLSClientConfig: &tls.Config{
				InsecureSkipVerify: true,
			},
		},
	}

	resp, err := client.Do(req)
	if err != nil {
		return fmt.Errorf("failed to send request: %w", err)
	}

	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	logrus.WithField("link", link).Info("downloading source")

	defer func() {
		logrus.WithFields(logrus.Fields{
			"link":        link,
			"status_code": resp.StatusCode,
			"filename":    filename,
		}).Info("source downloaded")
	}()

	if resp.StatusCode != http.StatusOK {
		return fmt.Errorf("unexpected status code: %d", resp.StatusCode)
	}

	bar := progressbar.DefaultBytes(resp.ContentLength, link)

	defer func() {
		if err := bar.Finish(); err != nil {
			logrus.WithError(err).Errorf("failed to finish bar")
		}
	}()

	reader := &ProgressDecorator{
		Reader: resp.Body,
		OnProgress: func(n int64) {
			if err := bar.Add64(n); err != nil {
				logrus.WithError(err).Errorf("failed to set value of bar")
			}
		},
	}

	if _, err = io.Copy(f, reader); err != nil {
		return fmt.Errorf("failed to copy response to cache file: %w", err)
	}

	return nil
}
