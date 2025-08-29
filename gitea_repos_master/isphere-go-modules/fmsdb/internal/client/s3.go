package client

import (
	"os"

	"github.com/aws/aws-sdk-go/aws"
	"github.com/aws/aws-sdk-go/aws/credentials"
	"github.com/aws/aws-sdk-go/aws/session"
	"github.com/aws/aws-sdk-go/service/s3"
)

type S3 struct {
}

func NewS3() *S3 {
	return &S3{}
}

func (t *S3) Acquire() *s3.S3 {
	config := &aws.Config{
		Credentials:      credentials.NewEnvCredentials(),
		DisableSSL:       aws.Bool(os.Getenv("AWS_DISABLE_SSL") == "true"),
		Endpoint:         aws.String(os.Getenv("AWS_ENDPOINT")),
		Region:           aws.String(os.Getenv("AWS_DEFAULT_REGION")),
		S3ForcePathStyle: aws.Bool(os.Getenv("AWS_FORCE_PATH_STYLE") == "true"),
	}

	if os.Getenv("APP_ENV") == "dev" {
		config.LogLevel = aws.LogLevel(aws.LogDebugWithRequestErrors)
	}

	sess := session.Must(session.NewSession(config))

	return s3.New(sess)
}
