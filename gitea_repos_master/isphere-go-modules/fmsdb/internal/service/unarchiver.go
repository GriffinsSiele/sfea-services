package service

import (
	"context"
	"fmt"
	"os"
	"os/exec"
	"path"
	"path/filepath"
	"strings"
	"time"

	"github.com/google/uuid"
	"github.com/sirupsen/logrus"
	"gopkg.in/alessio/shellescape.v1"
)

type UnarchiverService struct{}

func NewUnarchiverService() *UnarchiverService {
	return &UnarchiverService{}
}

func (t *UnarchiverService) Unarchive(_ context.Context, filename string) (string, error) {
	tmpDir := filepath.Join(os.TempDir(), uuid.NewString())
	if err := os.Mkdir(tmpDir, 0o0755); err != nil {
		return "", fmt.Errorf("create temp directory: %w", err)
	}

	defer func() {
		_ = os.RemoveAll(tmpDir)
	}()

	logrus.WithField("filename", filename).Info("unarchiving file")

	cmd := exec.Command("/usr/bin/env", "7z", "x", filename, fmt.Sprintf("-o%s", shellescape.Quote(tmpDir)))
	cmd.Stdout = os.Stdout
	cmd.Stderr = os.Stderr

	if err := cmd.Start(); err != nil {
		logrus.Info("please check required system dependencies installed: p7zip, unrar, unzip")

		return "", fmt.Errorf("7z: %w", err)
	}

	if err := cmd.Wait(); err != nil {
		return "", fmt.Errorf("7z failed to execute: %w", err)
	}

	files, err := os.ReadDir(tmpDir)
	if err != nil {
		return "", fmt.Errorf("read temp directory: %w", err)
	}

	for _, f := range files {
		if !strings.Contains(f.Name(), ".csv") {
			continue
		}

		name := t.filename()
		if err = os.Rename(filepath.Join(tmpDir, f.Name()), name); err != nil {
			return "", fmt.Errorf("rename temp file: %w", err)
		}

		return name, nil
	}

	return "", fmt.Errorf("no file found in archive: %s", os.Getenv("FMSDB_FILENAME"))
}

func (t *UnarchiverService) filename() string {
	var (
		filename         = os.Getenv("FMSDB_FILENAME")
		filenameExt      = filepath.Ext(filename)
		filenameBasename = strings.TrimSuffix(path.Base(filename), filenameExt)
	)

	filename = fmt.Sprintf("%s.%s%s", filenameBasename, time.Now().Format(time.RFC3339), filenameExt)
	filename = fmt.Sprintf("%s/%s", os.Getenv("BLUE_DIRECTORY"), filename)

	return filename
}
