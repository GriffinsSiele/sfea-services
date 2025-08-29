package config

import (
	"fmt"
	"io/fs"
	"os"
	"path/filepath"
	"regexp"
	"strings"
	"time"

	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/hook"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/tags"
	"github.com/davecgh/go-spew/spew"
	"github.com/getsentry/sentry-go"
	"github.com/gin-gonic/gin"
	"github.com/joho/godotenv"
	"github.com/mitchellh/mapstructure"
	"github.com/peterbourgon/mergemap"
	"github.com/sirupsen/logrus"
	"gopkg.in/yaml.v3"
)

type Config struct {
	Env         Env                   `yaml:"env"`
	Definitions map[string]any        `yaml:"definitions"`
	Providers   map[string]*Provider  `yaml:"providers"`
	CheckTypes  map[string]*CheckType `yaml:"check_types" mapstructure:"check_types"`
	Services    Services              `yaml:"services"`
}

func NewConfig() (*Config, error) {
	// load env
	if err := godotenv.Load(".env"); err != nil && !os.IsNotExist(err) {
		return nil, fmt.Errorf("failed to load .env file: %w", err)
	}

	if err := godotenv.Overload(".env.local"); err != nil && !os.IsNotExist(err) {
		return nil, fmt.Errorf("failed to overload .env.local file: %w", err)
	}

	lvl, err := logrus.ParseLevel(os.Getenv("LOG_LEVEL"))
	if err != nil {
		return nil, fmt.Errorf("failed to parse log level: %w", err)
	}

	logrus.SetLevel(lvl)

	// configure sentry
	if err = sentry.Init(sentry.ClientOptions{Dsn: os.Getenv("SENTRY_DSN")}); err != nil {
		logrus.WithError(err).Error("failed to init sentry")
	} else {
		logrus.AddHook(hook.NewSentryHook(os.Getenv("SENTRY_DSN")))
	}

	logrus.AddHook(tags.NewHook())

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
				logrus.WithField("environment:key", key).Fatal("required environment variable not exists")
			}

			logrus.WithField("environment:key", key).
				WithField("value", value).
				Debug("replace environment variable pattern")

			return []byte(value)
		})

		var tmpMap map[string]any
		if err = yaml.Unmarshal(res, &tmpMap); err != nil {
			return nil, fmt.Errorf("unmarshal config: %s: %w", filename, err)
		}

		tMap = mergemap.Merge(tMap, tmpMap)

		logrus.WithField("filename", filename).Debug("using config file")
	}

	var t Config
	if err = mapstructure.Decode(tMap, &t); err != nil {
		return nil, fmt.Errorf("mapstructure decode: %w", err)
	}

	if t.Env == EnvDevelopment {
		gin.SetMode(gin.DebugMode)

		for _, v := range os.Args {
			if v == "--verbose" {
				// dump config for visual control
				serialized, _ := yaml.Marshal(t)
				logrus.Debug(spew.Sprintln(string(serialized)))

				break
			}
		}
	}

	if t.Env == EnvProduction {
		gin.SetMode(gin.ReleaseMode)
	}

	return &t, nil
}

func (t *Config) GetDefinitionByName(name string) (map[string]any, error) {
	definition, ok := (t.Definitions["schema"].(map[string]any))[name].(map[string]any)
	if !ok {
		return nil, fmt.Errorf("unknown definition: %s", name)
	}
	return definition, nil
}

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

type Services struct {
	Clickhouse ClickhouseService `yaml:"clickhouse"`
	KeyDB      KeyDBService      `yaml:"keydb"`
	RabbitMQ   RabbitMQService   `yaml:"rabbitmq"`
}

const BaseDir = "configs"

var envVarMatch = regexp.MustCompile(`(?m)\$[A-Z\d_]+`)

// ---

type ClickhouseService struct {
	Addr     string `yaml:"addr,omitempty"`
	Timezone string `yaml:"timezone,omitempty"`
	PoolSize int    `yaml:"poolSize,omitempty"`
}

// ---

type KeyDBService struct {
	Addr         string        `yaml:"addr,omitempty"`
	Password     string        `yaml:"password,omitempty"`
	Database     int           `yaml:"database,omitempty"`
	PoolSize     int           `yaml:"poolSize,omitempty"`
	DialTimeout  time.Duration `yaml:"dialTimeout,omitempty"`
	ReadTimeout  time.Duration `yaml:"readTimeout,omitempty"`
	WriteTimeout time.Duration `yaml:"writeTimeout,omitempty"`
}

// ---

type RabbitMQService struct {
	Addr        string `yaml:"addr,omitempty"`
	Username    string `yaml:"username,omitempty"`
	Password    string `yaml:"password,omitempty"`
	VirtualHost string `yaml:"vhost,omitempty"`
}

// ---

type Provider struct {
	Endpoint  string `yaml:"endpoint,omitempty"`
	Title     string `yaml:"title,omitempty"`
	HTTPBasic struct {
		Enabled  bool   `yaml:"enabled,omitempty"`
		Username string `yaml:"username,omitempty"`
		Password string `yaml:"password,omitempty"`
	} `yaml:"http_basic" mapstructure:"http_basic,omitempty"`
	Headers []*ProviderHeader `yaml:"headers,omitempty"`
}

type ProviderHeader struct {
	Name  string `yaml:"name,omitempty"`
	Value string `yaml:"value,omitempty"`
}

// ---

type CheckType struct {
	Enabled  bool             `yaml:"enabled"`
	Source   *CheckTypeSource `yaml:"source,omitempty"`
	Schema   map[string]any   `yaml:"schema,omitempty"`
	Mutator  Mutator          `yaml:"mutator,omitempty"`
	Upstream Upstream         `yaml:"upstream,omitempty"`
	Produce  map[string]any   `yaml:"produce,omitempty"`
}

func (c *CheckType) Scope() string {
	if s := c.Upstream.RabbitMQ.Scope; s != "" {
		return s
	}

	if s := c.Upstream.KeyDB.Scope; s != "" {
		return s
	}

	return ""
}

type CheckTypeSource struct {
	Code string `yaml:"code,omitempty"`
}

// ---

type Mutator struct {
	Template   MutatorTemplate    `yaml:"template"`
	StatusCode *MutatorStatusCode `yaml:"status_code" mapstructure:"status_code"`
}

type MutatorTemplate struct {
	Records string `yaml:"records,omitempty"`
}

type MutatorStatusCode struct {
	NonStandard        bool   `yaml:"non_standard,omitempty" mapstructure:"non_standard"`
	Format             string `yaml:"format,omitempty" mapstructure:"format"`
	PropertyPath       string `yaml:"property_path,omitempty" mapstructure:"property_path"`
	DetailPropertyPath string `yaml:"detail_property_path,omitempty" mapstructure:"detail_property_path"`
}

// ---

type Upstream struct {
	Provider string `yaml:"provider,omitempty"`

	KeyDB UpstreamKeyDB `yaml:"keydb"`

	RabbitMQ struct {
		Enabled bool   `yaml:"enabled"`
		Scope   string `yaml:"scope,omitempty"`

		Async struct {
			Enabled bool   `yaml:"enabled"`
			Scope   string `yaml:"scope,omitempty"`
		} `yaml:"await,omitempty"`
	} `yaml:"rabbitmq,omitempty"`

	TCP *UpstreamTCP `yaml:"tcp,omitempty"`

	Command *UpstreamCommand `yaml:"command,omitempty"`

	GraphQL *struct {
		Query    string `yaml:"query,omitempty"`
		Template struct {
			Query string `yaml:"query,omitempty"`
		}
	} `yaml:"graphql,omitempty"`
}

type UpstreamTCP struct {
	SimpleProtocol *UpstreamTCPSimpleProtocol `yaml:"simple_protocol,omitempty" mapstructure:"simple_protocol"`
	Proxy          Proxy                      `yaml:"proxy,omitempty"`
	Query          string                     `yaml:"query,omitempty"`
	Template       *UpstreamTCPTemplate       `yaml:"template,omitempty"`
	Retry          *UpstreamTCPRetry          `yaml:"retry,omitempty"`
}

type UpstreamTCPRetry struct {
	MaxCount           int `yaml:"max_count,omitempty" mapstructure:"max_count"`
	ExpectedStatusCode int `yaml:"expected_status_code,omitempty" mapstructure:"expected_status_code"`
}

type UpstreamTCPSimpleProtocol struct {
	StatusCodeWhenFound    int `yaml:"status_code_when_found,omitempty" mapstructure:"status_code_when_found"`
	StatusCodeWhenNotFound int `yaml:"status_code_when_not_found,omitempty" mapstructure:"status_code_when_not_found"`
}

type Proxy struct {
	Enabled bool   `yaml:"enabled,omitempty"`
	URL     string `yaml:"url,omitempty"`
}

type UpstreamCommand struct {
	Query    string                   `yaml:"query,omitempty"`
	Template *UpstreamCommandTemplate `yaml:"template,omitempty"`
}

type UpstreamTCPTemplate struct {
	Query string `yaml:"query,omitempty"`
}

type UpstreamCommandTemplate struct {
	Query string `yaml:"query,omitempty"`
}

type UpstreamKeyDB struct {
	Enabled   bool   `yaml:"enabled"`
	Scope     string `yaml:"scope,omitempty"`
	TTL       string `yaml:"ttl,omitempty"`
	TTLFailed string `yaml:"ttl_failed,omitempty" mapstructure:"ttl_failed"`
}

func (t *UpstreamKeyDB) GetTTL() *time.Duration {
	return t.getTTL(t.TTL, "TTL")
}

func (t *UpstreamKeyDB) GetTTLFailed() *time.Duration {
	return t.getTTL(t.TTLFailed, "TTLFailed")
}

func (t *UpstreamKeyDB) getTTL(v, name string) *time.Duration {
	duration, err := time.ParseDuration(v)
	if err != nil {
		logrus.WithError(err).WithField("scope", t.Scope).Errorf("parse duration (.%s): %v: %v", name, v, err)
	}

	return &duration
}

// ---

type Env string

const (
	EnvDevelopment Env = "dev"
	EnvProduction  Env = "prod"
)
