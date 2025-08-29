package service

import (
	"context"
	"encoding/csv"
	"encoding/gob"
	"errors"
	"fmt"
	"io"
	"os"
	"path"
	"strconv"
	"strings"
	"time"

	"git.i-sphere.ru/isphere-go-modules/fmsdb/internal/util"
	"github.com/RoaringBitmap/roaring"
	"github.com/jedib0t/go-pretty/v6/progress"
	"github.com/sirupsen/logrus"
)

type IndexerService struct{}

func NewIndexerService() *IndexerService {
	return &IndexerService{}
}

func (t *IndexerService) Build(_ context.Context, filename string) (string, error) {
	file, err := os.Open(filename)
	if err != nil {
		return "", fmt.Errorf("open file: %w", err)
	}

	fileInfo, err := file.Stat()
	if err != nil {
		return "", fmt.Errorf("stat file: %w", err)
	}

	//goland:noinspection GoUnhandledErrorResult
	defer file.Close()

	var (
		progressTracker = &progress.Tracker{
			Message: "indexing",
			Total:   fileInfo.Size(),
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

	indexFilename := t.filename()
	indexFile, err := os.OpenFile(indexFilename, os.O_RDWR|os.O_CREATE, 0o0644)
	if err != nil {
		progressTracker.MarkAsErrored()

		<-progressRendered

		return "", fmt.Errorf("open tmp file: %w", err)
	}

	//goland:noinspection GoUnhandledErrorResult
	defer indexFile.Close()

	index := make(Index, 10_000)

	reader := csv.NewReader(file)

	_, _ = reader.Read() // skip first line headers

	for {
		record, err := reader.Read()
		if err != nil {
			if errors.Is(err, io.EOF) {
				break
			}

			progressTracker.MarkAsErrored()

			<-progressRendered

			return "", fmt.Errorf("read file: %w", err)
		}

		if len(record) != 2 {
			progressTracker.MarkAsErrored()

			<-progressRendered

			return "", errors.New("unsupported file struct, required only two columns")
		}

		var (
			seriesInt int
			series    uint16
			numberInt int
			number    uint32
		)

		if seriesInt, err = strconv.Atoi(record[0]); err != nil {
			progressTracker.SetValue(reader.InputOffset())
			logrus.WithField("series", record[0]).Warn("cannot cast series as int")

			continue
		}

		if numberInt, err = strconv.Atoi(record[1]); err != nil {
			progressTracker.SetValue(reader.InputOffset())
			logrus.WithField("number", record[1]).Warn("cannot cast number as int")

			continue
		}

		series = uint16(seriesInt)
		number = uint32(numberInt)

		if index[series] == nil {
			index[series] = roaring.NewBitmap()
		}

		index[series].Add(number)
		progressTracker.SetValue(reader.InputOffset())
	}

	progressTracker.MarkAsDone()

	<-progressRendered

	encoder := gob.NewEncoder(indexFile)
	if err = encoder.Encode(index); err != nil {
		return "", fmt.Errorf("serialize: %w", err)
	}

	return indexFilename, nil
}

func (t *IndexerService) filename() string {
	var (
		filename         = os.Getenv("FMSDB_FILENAME")
		filenameExt      = path.Ext(filename)
		filenameBasename = strings.TrimSuffix(path.Base(filename), filenameExt)
	)

	filename = fmt.Sprintf("%s.%s%s", filenameBasename, time.Now().Format(time.RFC3339), filenameExt)
	filename = fmt.Sprintf("%s/%s.1", os.Getenv("BLUE_DIRECTORY"), filename)

	return filename
}

// ---

type Index map[uint16]*roaring.Bitmap
