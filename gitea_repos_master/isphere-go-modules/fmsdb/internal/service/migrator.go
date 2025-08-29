package service

import (
	"context"
	"fmt"
	"os"
	"path"
	"strconv"

	"git.i-sphere.ru/isphere-go-modules/fmsdb/internal/client"
	"github.com/aws/aws-sdk-go/aws"
	"github.com/aws/aws-sdk-go/service/s3"
	"github.com/sirupsen/logrus"
)

type MigratorService struct {
	passportRepository *PassportRepository
	s3                 *client.S3
}

func NewMigratorService(
	passportRepository *PassportRepository,
	s3 *client.S3,
) *MigratorService {
	return &MigratorService{
		passportRepository: passportRepository,
		s3:                 s3,
	}
}

func (t *MigratorService) Migrate(ctx context.Context, filename string) (string, error) {
	if err := t.passportRepository.InitWithFilename(ctx, filename); err != nil {
		return "", fmt.Errorf("init passport repository with filename: %w", err)
	}

	totalCount := t.passportRepository.Count(ctx)
	totalCountAtLeast, err := strconv.Atoi(os.Getenv("CHECK_TOTAL_COUNT_AT_LEAST"))
	if err != nil {
		return "", fmt.Errorf("cast CHECK_TOTAL_COUNT_AT_LEAST: %w", err)
	}

	if totalCount < totalCountAtLeast {
		return "", fmt.Errorf("total count less than %d items", totalCountAtLeast)
	}

	svc := t.s3.Acquire()

	if _, err = svc.HeadBucket(&s3.HeadBucketInput{
		Bucket: aws.String(os.Getenv("AWS_BUCKET")),
	}); err != nil {
		logrus.WithError(err).Warnf("s3 bucket info: %v", err)

		if _, err = svc.CreateBucket(&s3.CreateBucketInput{
			Bucket: aws.String(os.Getenv("AWS_BUCKET")),
		}); err != nil {
			return "", fmt.Errorf("failed to create s3 bucket: %w", err)
		}
	}

	reader, err := os.Open(filename)
	if err != nil {
		return "", fmt.Errorf("failed to open file: %w", err)
	}

	//goland:noinspection GoUnhandledErrorResult
	defer reader.Close()

	key := os.Getenv("FMSDB_FILENAME") + ".1"

	if _, err = svc.PutObjectWithContext(ctx, &s3.PutObjectInput{
		Bucket: aws.String(os.Getenv("AWS_BUCKET")),
		Key:    aws.String(path.Join("fmsdb", key)),
		Body:   reader,
	}); err != nil {
		return "", fmt.Errorf("failed to upload file: %w", err)
	}

	return fmt.Sprintf("s3://%s/%s", os.Getenv("AWS_BUCKET"), key), nil
}
