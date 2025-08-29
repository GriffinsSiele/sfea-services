package service

import (
	"context"
	"encoding/gob"
	"fmt"
	"io"
	"os"
	"path"
	"regexp"
	"strings"

	"git.i-sphere.ru/isphere-go-modules/fmsdb/internal/client"
	"github.com/aws/aws-sdk-go/aws"
	"github.com/aws/aws-sdk-go/service/s3"
)

type PassportRepository struct {
	index Index
	s3    *client.S3

	initialized bool
}

func NewPassportRepository(s3 *client.S3) *PassportRepository {
	return &PassportRepository{
		s3: s3,
	}
}

func (t *PassportRepository) Initialized() bool {
	return t.initialized
}

func (t *PassportRepository) Init(ctx context.Context) error {
	if err := t.InitWithFilename(ctx, t.Filename()); err != nil {
		return fmt.Errorf("init index repository: %w", err)
	}

	return nil
}

func (t *PassportRepository) InitWithFilename(_ context.Context, filename string) error {
	if t.initialized {
		return nil
	}

	var f io.ReadCloser

	defer func() {
		if f != nil {
			//goland:noinspection GoUnhandledErrorResult
			f.Close()
		}
	}()

	if strings.HasPrefix(filename, "s3") {
		re := regexp.MustCompile(`^s3://([^/]+)/(.+)$`)
		if !re.MatchString(filename) {
			return fmt.Errorf("failed to match s3 string: %s", filename)
		}

		var (
			parts  = re.FindStringSubmatch(filename)
			bucket = parts[1]
			key    = parts[2]
		)

		svc := t.s3.Acquire()
		out, err := svc.GetObject(&s3.GetObjectInput{
			Bucket: aws.String(bucket),
			Key:    aws.String(path.Join("fmsdb", key)),
		})

		if err != nil {
			return fmt.Errorf("failed to get s3 obj: %w", err)
		}

		f = out.Body
	} else {
		if name, err := os.Readlink(filename); err == nil {
			filename = name
		}

		var err error
		f, err = os.Open(filename)
		if err != nil {
			return fmt.Errorf("open file: %w", err)
		}
	}

	decoder := gob.NewDecoder(f)
	if err := decoder.Decode(&t.index); err != nil {
		return fmt.Errorf("decode index file: %w", err)
	}

	t.initialized = true

	return nil
}

func (t *PassportRepository) Exists(_ context.Context, series uint16, number uint32) bool {
	return t.index[series] != nil && t.index[series].Contains(number)
}

func (t *PassportRepository) Count(_ context.Context) int {
	var count int
	for _, numbers := range t.index {
		count += len(numbers.ToArray())
	}

	return count
}

func (t *PassportRepository) Filename() string {
	return fmt.Sprintf("s3://%s/%s.1", os.Getenv("AWS_BUCKET"), os.Getenv("FMSDB_FILENAME"))
}
