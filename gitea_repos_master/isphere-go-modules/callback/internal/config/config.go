package config

import (
	"fmt"
	"io/fs"
	"os"
	"path/filepath"
	"regexp"
	"strings"

	"github.com/charmbracelet/log"
	"github.com/getsentry/sentry-go"
	"github.com/joho/godotenv"
	"github.com/mitchellh/mapstructure"
	"github.com/peterbourgon/mergemap"
	"sigs.k8s.io/yaml"
)

type Config struct {
	Env       string               `yaml:"env"`
	Providers map[string]*Provider `yaml:"providers"`
	Rules     map[string]*Rule     `yaml:"rules"`
	Services  Services             `yaml:"services"`
}

func NewConfig() (*Config, error) {
	if err := godotenv.Load(".env"); err != nil {
		return nil, fmt.Errorf("load .env: %w", err)
	}

	_ = godotenv.Overload(".env.local")

	sentry.Init(sentry.ClientOptions{
		Dsn: os.Getenv("SENTRY_DSN"),
	})

	log.SetFormatter(log.JSONFormatter)

	filenames, err := yamlFilenames()
	if err != nil {
		return nil, fmt.Errorf("yaml filenames: %w", err)
	}

	tMap := make(map[string]any)

	for _, filename := range filenames {
		contents, err := os.ReadFile(filename)
		if err != nil {
			return nil, fmt.Errorf("read config: %s: %w", filename, err)
		}

		res := envVarMatch.ReplaceAllFunc(contents, func(bytes []byte) []byte {
			key := strings.TrimPrefix(string(bytes), "$")

			value, ok := os.LookupEnv(key)
			if !ok {
				log.With("key", key).Fatal("required environment variable not exists")
			}

			log.With("key", key).With("value", value).Debug("found environment variable")

			return []byte(value)
		})

		var tmpMap map[string]any
		if err = yaml.Unmarshal(res, &tmpMap); err != nil {
			return nil, fmt.Errorf("unmarshal config: %s: %w", filename, err)
		}

		tMap = mergemap.Merge(tMap, tmpMap)

		log.With("filename", filename).Info("loaded config")
	}

	var t Config
	if err = mapstructure.Decode(tMap, &t); err != nil {
		return nil, fmt.Errorf("mapstructure decode: %w", err)
	}

	return &t, nil
}

const BaseDir = "configs"

var envVarMatch = regexp.MustCompile(`(?m)\$[A-Z\d_]+`)

func yamlFilenames() ([]string, error) {
	var filenames []string

	if err := filepath.Walk(BaseDir, func(filename string, info fs.FileInfo, err error) error {
		if err != nil {
			return err
		}

		if strings.HasSuffix(info.Name(), ".yaml") {
			filenames = append(filenames, filename)
		}

		return nil
	}); err != nil {
		return nil, fmt.Errorf("walk on the config dir: %w", err)
	}

	return filenames, nil
}

// ---

type Services struct {
	RabbitMQ RabbitMQService `yaml:"rabbitmq"`
}

// ---

type RabbitMQService struct {
	Addr        string `yaml:"addr"`
	Username    string `yaml:"username"`
	Password    string `yaml:"password"`
	VirtualHost string `yaml:"vhost"`
}

// ---

type Provider struct {
	Endpoint string `yaml:"endpoint"`
}

// ---

type Rule struct {
	Name       string         `yaml:"-"`
	Enabled    bool           `yaml:"enabled"`
	Pattern    string         `yaml:"pattern"`
	Schema     map[string]any `yaml:"schema"`
	Downstream Downstream     `yaml:"downstream"`
	Mutator    Mutator        `yaml:"mutator"`

	patternExp *regexp.Regexp
}

func (t *Rule) GetPatternExpr() *regexp.Regexp {
	if t.Pattern == "" {
		return nil
	}

	if t.patternExp != nil {
		return t.patternExp
	}

	t.patternExp = regexp.MustCompile(t.Pattern)

	return t.patternExp
}

// ---

type Downstream struct {
	Proxy struct {
		Enabled            bool   `yaml:"enabled"`
		Host               string `yaml:"host"`
		RewriteMethod      string `yaml:"rewriteMethod"`
		RewritePath        string `yaml:"rewritePath"`
		ExpectedStatusCode int    `yaml:"expectedStatusCode"`
	} `yaml:"proxy"`

	RabbitMQ struct {
		Enabled bool   `yaml:"enabled"`
		Scope   string `yaml:"scope"`
	} `yaml:"rabbitmq"`
}

// ---

type Mutator struct {
	Marshaller MutatorMarshaller `json:"marshaller"`
	Key        Template          `json:"key"`
	Template   string            `json:"template"`
}

type MutatorMarshaller string

const (
	MutatorMarshallerForm MutatorMarshaller = "form"
	MutatorMarshallerJSON MutatorMarshaller = "json"
	MutatorMarshallerNone MutatorMarshaller = "none"
)

// ---

type Template struct {
	Template string `yaml:"template"`
}
