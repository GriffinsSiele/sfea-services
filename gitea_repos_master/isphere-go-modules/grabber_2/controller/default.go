package controller

import (
	"archive/zip"
	"bytes"
	"encoding/xml"
	"errors"
	"fmt"
	"github.com/xitongsys/parquet-go/writer"
	"io"
	"net/http"
	"os"
	"path"
	"runtime"
	"time"

	"git.i-sphere.ru/grabber2/model"
	"github.com/sirupsen/logrus"
)

const DataURL = "https://islod.obrnadzor.gov.ru/rlic/opendata/"

type DefaultController struct{}

func NewDefaultController() *DefaultController {
	return &DefaultController{}
}

func (t *DefaultController) Handle(resp io.Writer) {
	var (
		limit       = 10
		xmlFilename = path.Join(os.TempDir(), "obrnadzor_7701537808_fbdrl.xml")
	)

	stat, err := os.Stat(xmlFilename)
	if err != nil && os.IsNotExist(err) || -time.Until(stat.ModTime()).Minutes() > 60 {
		if err1 := t.downloadAndUnpack(DataURL, xmlFilename); err1 != nil {
			logrus.WithError(err1).Errorf("failed to download and unpack")
			return
		}
	}

	logrus.WithField("filename", xmlFilename).Info("use local file")

	xmlFile, err := os.Open(xmlFilename)
	if err != nil {
		logrus.WithError(err).Error("cannot open xml file")
		return
	}

	//goland:noinspection GoUnhandledErrorResult
	defer xmlFile.Close()

	var (
		xmlDecoder = xml.NewDecoder(xmlFile)
		j          int
	)

	w, err := writer.NewParquetWriterFromWriter(resp, new(model.License), int64(runtime.GOMAXPROCS(runtime.NumCPU())))
	if err != nil {
		logrus.WithError(err).Error("failed to make parquet writer")
		return
	}

l:
	for xmlToken, _ := xmlDecoder.Token(); xmlToken != nil; xmlToken, _ = xmlDecoder.Token() {
		switch xmlElement := xmlToken.(type) {
		case xml.StartElement:
			if xmlElement.Name.Local != "license" {
				continue
			}

			j++
			if limit > 0 && j > limit {
				break l
			}

			var license model.License
			if err = xmlDecoder.DecodeElement(&license, &xmlElement); err != nil {
				logrus.WithError(err).Error("failed to decode xml element")
				break l
			}

			if license.DateLicDoc != nil && license.DateLicDoc.Value != nil {
				ts := int32(license.DateLicDoc.Value.Unix())
				license.DateLicDocTime = &ts
			}

			if license.DateEnd != nil && license.DateEnd.Value != nil {
				ts := int32(license.DateEnd.Value.Unix())
				license.DateEndTime = &ts
			}

			if err = w.Write(license); err != nil {
				logrus.WithError(err).Error("failed to write license")
				break l
			}
		}
	}

	if err = w.WriteStop(); err != nil {
		logrus.WithError(err).Error("failed to stop writer")
	}
}

func (t *DefaultController) downloadAndUnpack(downloadURL string, target string) error {
	logrus.WithField("url", DataURL).Info("fetching remote data")

	req, err := http.NewRequest(http.MethodGet, downloadURL, http.NoBody)
	if err != nil {
		return fmt.Errorf("failed to make new request: %w", err)
	}

	resp, err := http.DefaultClient.Do(req)
	if err != nil {
		return fmt.Errorf("failed to fetch data: %w", err)
	}

	//goland:noinspection GoUnhandledErrorResult
	defer resp.Body.Close()

	respBody, err := io.ReadAll(resp.Body)
	if err != nil {
		return fmt.Errorf("failed to read response body: %w", err)
	}

	arc, err := zip.NewReader(bytes.NewReader(respBody), int64(len(respBody)))
	if err != nil {
		return fmt.Errorf("failed to open archive body: %w", err)
	}

	if len(arc.File) != 1 {
		return errors.New("unsupported archive contents")
	}

	logrus.WithField("filename", arc.File[0].Name).Info("unpacking remote archive")

	xmlFile, err := arc.File[0].Open()
	if err != nil {
		return fmt.Errorf("failed to open archive file: %w", err)
	}

	//goland:noinspection GoUnhandledErrorResult
	defer xmlFile.Close()

	osFile, err := os.OpenFile(target, os.O_RDWR|os.O_CREATE, 0644)
	if err != nil {
		return fmt.Errorf("failed to open os file: %w", err)
	}

	//goland:noinspection GoUnhandledErrorResult
	defer osFile.Close()

	if _, err = io.Copy(osFile, xmlFile); err != nil {
		return fmt.Errorf("failed to copy file contents: %w", err)
	}

	//goland:noinspection GoUnhandledErrorResult
	defer xmlFile.Close()

	return nil
}
