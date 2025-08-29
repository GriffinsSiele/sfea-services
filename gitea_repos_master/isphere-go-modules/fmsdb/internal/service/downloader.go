package service

import (
	"context"
	"fmt"
	"io"
	"net/http"
	"os"
	"path"
	"strconv"
	"strings"
	"time"

	"git.i-sphere.ru/isphere-go-modules/fmsdb/internal/middleware"
	"git.i-sphere.ru/isphere-go-modules/fmsdb/internal/util"
	"github.com/jedib0t/go-pretty/v6/progress"
	"github.com/sirupsen/logrus"
)

type DownloaderService struct {
	httpClient *http.Client
}

func NewDownloaderService() *DownloaderService {
	return &DownloaderService{
		httpClient: http.DefaultClient,
	}
}

func (t *DownloaderService) Download(_ context.Context, url string) (string, error) {
	filename := t.filename(path.Base(url))

	httpRequest, err := http.NewRequest(http.MethodGet, url, http.NoBody)
	if err != nil {
		return "", fmt.Errorf("new HTTP request: %w", err)
	}

	httpResponse, err := t.httpClient.Do(httpRequest)
	if err != nil {
		return "", fmt.Errorf("HTTP request: %w", err)
	}

	//goland:noinspection GoUnhandledErrorResult
	defer httpResponse.Body.Close()

	downloadedFileSize, err := strconv.Atoi(httpResponse.Header.Get("Content-Length"))
	if err != nil {
		return "", fmt.Errorf("cast Content-Length: %w", err)
	}

	var (
		progressTracker = &progress.Tracker{
			Message: "downloading",
			Total:   int64(downloadedFileSize),
			Units:   progress.UnitsBytes,
		}
		progressWriter   = util.NewProgressWriter(progressTracker)
		progressRendered = make(chan bool, 1)
	)

	go func() {
		progressWriter.Render()

		for progressWriter.IsRenderInProgress() {
		}

		progressRendered <- true
	}()

	downloadedFile, err := os.OpenFile(filename, os.O_CREATE|os.O_RDWR, 0o0644)
	if err != nil {
		progressTracker.MarkAsErrored()

		<-progressRendered

		return "", fmt.Errorf("open output file: %w", err)
	}

	//goland:noinspection GoUnhandledErrorResult
	defer downloadedFile.Close()

	progressReader := middleware.NewProgressReader(httpResponse.Body, func(r int64) {
		progressTracker.Increment(r)
	})

	logrus.WithFields(logrus.Fields{
		"filename": filename,
		"url":      url,
	}).Info("downloading started")

	if _, err = io.Copy(downloadedFile, progressReader); err != nil {
		progressTracker.MarkAsErrored()

		<-progressRendered

		return "", fmt.Errorf("download source: %w", err)
	}

	progressTracker.MarkAsDone()

	<-progressRendered

	return filename, nil
}

func (t *DownloaderService) filename(filename string) string {
	var (
		filenameExt      = path.Ext(filename)
		filenameBasename = strings.TrimSuffix(path.Base(filename), filenameExt)
	)

	filename = fmt.Sprintf("%s.%s%s", filenameBasename, time.Now().Format(time.RFC3339), filenameExt)
	filename = fmt.Sprintf("%s/%s", os.Getenv("BLUE_DIRECTORY"), filename)

	return filename
}
