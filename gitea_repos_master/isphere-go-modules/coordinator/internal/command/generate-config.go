package command

import (
	"bufio"
	"database/sql"
	"encoding/json"
	"fmt"
	"io"
	"os"
	"path"
	"strconv"
	"strings"
	"time"

	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/config"
	_ "github.com/go-sql-driver/mysql"
	"github.com/sirupsen/logrus"
	"github.com/urfave/cli/v2"
	"gopkg.in/yaml.v2"
)

/*
go run . generate:config -source gosuslugi_phone -plugin GosuslugiPlugin -source-name Gosuslugi --dry-run
go run . generate:config -source ok_phone -plugin OKPlugin -source-name OK --dry-run
go run . generate:config -source twitter_phone -plugin TwitterPlugin -source-name Twitter --dry-run
go run . generate:config -source instagram_phone -plugin InstagramPlugin -source-name Instagram --dry-run
go run . generate:config -source facebook_phone -plugin FacebookPlugin -source-name Facebook --dry-run
go run . generate:config -source emt_phone -plugin EMTPlugin -source-name emt --dry-run
go run . generate:config -source skype_phone -plugin SkypePlugin -source-name Skype --dry-run
go run . generate:config -source telegram_phone -plugin TelegramPlugin -source-name Telegram --dry-run
go run . generate:config -source listorg_phone -plugin ListOrgPlugin -source-name egrul --dry-run
go run . generate:config -source pochta_phone -plugin PochtaPlugin -source-name Pochta --dry-run
*/

type GenerateConfig struct {
}

func NewGenerateConfig() *GenerateConfig {
	return new(GenerateConfig)
}

func (t *GenerateConfig) Describe() *cli.Command {
	return &cli.Command{
		Category: "generate",
		Name:     "generate:config",
		Action:   t.Action,
		Flags: cli.FlagsByName{
			&cli.BoolFlag{
				Name:  "dry-run",
				Value: false,
			},
			&cli.StringFlag{
				Name:     "source",
				Aliases:  []string{"n"},
				Required: true,
			},
			&cli.StringFlag{
				Name:     "plugin",
				Required: true,
			},
			&cli.StringFlag{ // delete it later
				Name:     "source-name",
				Required: true,
			},
			&cli.StringFlag{
				Name:  "default-tcp-template-query-payload",
				Value: `"phone" (trimPrefix "+" .phone)`,
			},
			&cli.BoolFlag{
				Name:  "default-enabled",
				Value: true,
			},
			&cli.StringFlag{
				Name:  "default-source-code",
				Value: "main-service",
			},
			&cli.StringFlag{
				Name: "default-schema",
				// language=json
				Value: `{
  "allOf": [
    {
      "$ref": "#/definitions/schema/default"
    },
    {
      "$ref": "#/definitions/schema/phone"
    }
  ]
}`,
			},
			&cli.StringFlag{
				Name:  "default-upstream-provider",
				Value: "main-service",
			},
			&cli.BoolFlag{
				Name:  "default-upstream-keydb-enabled",
				Value: true,
			},
			&cli.DurationFlag{
				Name:  "default-upstream-keydb-ttl",
				Value: 24 * time.Hour,
			},
			&cli.DurationFlag{
				Name:  "default-upstream-keydb-ttl-failed",
				Value: 30 * time.Second,
			},
			&cli.BoolFlag{
				Name:  "default-upstream-rabbitmq-enabled",
				Value: true,
			},
		},
	}
}

func (t *GenerateConfig) Action(c *cli.Context) error {
	var w io.Writer
	if !c.Bool("dry-run") {
		return fmt.Errorf("not implemented")
	} else {
		w = os.Stdout
	}

	if err := t.generateBaseYaml(c, w); err != nil {
		return fmt.Errorf("failed to generate base yaml: %w", err)
	}

	if err := t.generateExampleFile(c, w); err != nil {
		return fmt.Errorf("failed to generate example file: %w", err)
	}

	return nil
}

func (t *GenerateConfig) generateBaseYaml(c *cli.Context, w io.Writer) error {
	sourceName := c.String("source")

	ct := &config.CheckType{
		Enabled: c.Bool("default-enabled"),
		Source: &config.CheckTypeSource{
			Code: c.String("default-source-code"),
		},
		Upstream: config.Upstream{
			Provider: c.String("default-upstream-provider"),
		},
	}

	if ok := c.Bool("default-upstream-keydb-enabled"); ok {
		ct.Upstream.KeyDB.Enabled = ok
		ct.Upstream.KeyDB.Scope = sourceName
		ct.Upstream.KeyDB.TTL = c.Duration("default-upstream-keydb-ttl").String()
		ct.Upstream.KeyDB.TTLFailed = c.Duration("default-upstream-keydb-ttl-failed").String()
	}

	if ok := c.Bool("default-upstream-rabbitmq-enabled"); ok {
		ct.Upstream.RabbitMQ.Enabled = ok
		ct.Upstream.RabbitMQ.Scope = sourceName
	}

	if err := json.Unmarshal([]byte(c.String("default-schema")), &ct.Schema); err != nil {
		return fmt.Errorf("failed to unmarshal schema: %w", err)
	}

	ct.Upstream.TCP = &config.UpstreamTCP{
		Template: &config.UpstreamTCPTemplate{
			// language=gotemplate
			Query: fmt.Sprintf(`GET /2.00/handler.php?{{ QueryArguments
"auth" (Env "MODULE_MAIN_SERVICE_ACCESS_TOKEN")
"plugin" "%s"
"checktype" "%s"
%s
}} HTTP/1.1
`,
				c.String("plugin"),
				sourceName,
				c.String("default-tcp-template-query-payload"),
			),
		},
	}

	db, err := sql.Open("mysql", os.Getenv("MAIN_SERVICE_SQL_DSN"))
	if err != nil {
		return fmt.Errorf("failed to open db: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer db.Close()

	rows, err := db.QueryContext(c.Context,
		// language=mysql
		`select f.name,
       f.type
from Field f
where f.source_name = ?
  and f.checktype = ?
  and f.name != ''`, c.String("source-name"), sourceName)

	mutatorFields := make([]string, 0)
	produceFields := make(map[string]map[string]string)

	for rows.Next() {
		var name, typ string
		if err := rows.Scan(&name, &typ); err != nil {
			return fmt.Errorf("failed to scan: %w", err)
		}

		produceFieldOpts := map[string]string{}
		if typ == "string" {
			produceFieldOpts["type"] = "string"
		} else if typ == "datetime" {
			produceFieldOpts["type"] = "string"
			produceFieldOpts["format"] = "date-time"
		} else if typ == "integer" {
			produceFieldOpts["type"] = "integer"
		} else if typ == "address" {
			produceFieldOpts["type"] = "string"
		} else if typ == "text" {
			produceFieldOpts["type"] = "string"
		} else if typ == "nick" {
			produceFieldOpts["type"] = "string"
		} else if typ == "phone" {
			produceFieldOpts["type"] = "string"
		} else if typ == "email" {
			produceFieldOpts["type"] = "string"
			produceFieldOpts["format"] = "email"
		} else if typ == "skype" {
			produceFieldOpts["type"] = "string"
		} else {
			if typ == "url" {
				produceFieldOpts["type"] = "string"
				produceFieldOpts["format"] = "url"
			} else if typ == "image" {
				produceFieldOpts["type"] = "string"
				produceFieldOpts["contentEncoding"] = "base64"
			} else {
				return fmt.Errorf("unknown field type: %s", typ)
			}
		}

		mutatorFields = append(mutatorFields, fmt.Sprintf("{{ UnsafeField %s .%s }}", strconv.Quote(name), name))

		produceFields[name] = produceFieldOpts
	}

	if len(mutatorFields) == 0 {
		return fmt.Errorf("no mutator fields found")
	}

	var mutatorSb strings.Builder
	mutatorSb.WriteString("{{- range .records }}\n")
	for n, mutatorField := range mutatorFields {
		prefix := "  "
		if n == 0 {
			prefix = "- "
		}
		mutatorSb.WriteString(prefix + mutatorField + "\n")
	}
	mutatorSb.WriteString("{{- end }}\n")

	ct.Mutator.Template.Records = mutatorSb.String()
	ct.Mutator.StatusCode = &config.MutatorStatusCode{
		NonStandard:        true,
		Format:             "json",
		PropertyPath:       "code",
		DetailPropertyPath: "message",
	}

	produce := make(map[string]any)
	for k, v := range produceFields {
		produce[k] = v
	}
	p := map[string]any{
		"type": "array",
		"items": map[string]any{
			"type":       "object",
			"properties": produce,
		},
	}
	ct.Produce = p

	// wrap in specific map
	cfg := make(map[string]map[string]*config.CheckType)
	cfg["check_types"] = make(map[string]*config.CheckType)
	cfg["check_types"][sourceName] = ct

	filename := path.Join("configs", "check_types", fmt.Sprintf("%s.yaml", sourceName))
	if _, err = os.Stat(filename); err == nil && !promptYesNo(filename) {
		return nil
	}
	f, err := os.OpenFile(filename, os.O_CREATE|os.O_WRONLY|os.O_TRUNC, 0644)
	if err != nil {
		return fmt.Errorf("failed to open file: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer f.Close()

	if err = yaml.NewEncoder(f).Encode(cfg); err != nil {
		return fmt.Errorf("failed to encode config: %w", err)
	}
	logrus.WithField("filename", filename).Info("wrote config")

	return nil
}

func (t *GenerateConfig) generateExampleFile(c *cli.Context, w io.Writer) error {
	sourceName := c.String("source")

	filename := path.Join("examples", "checks", fmt.Sprintf("%s.sh", sourceName))
	if _, err := os.Stat(filename); err == nil && !promptYesNo(filename) {
		return nil
	}
	f, err := os.OpenFile(filename, os.O_CREATE|os.O_WRONLY|os.O_TRUNC, 0644)
	if err != nil {
		return fmt.Errorf("failed to open file: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer f.Close()

	//goland:noinspection GoUnhandledErrorResult
	fmt.Fprintln(f, "#!/bin/sh")
	//goland:noinspection GoUnhandledErrorResult
	fmt.Fprintf(f, "go run . invoke --scope %s -- \\\n  --phone +79772776278\n", sourceName)
	//goland:noinspection GoUnhandledErrorResult
	fmt.Fprintln(f)
	logrus.WithField("filename", filename).Info("wrote example")

	return nil
}

func promptYesNo(filename string) bool {
	fmt.Printf("File %s already exists. Are you sure you want to replace it? (y/n): ", filename)
	reader := bufio.NewReader(os.Stdin)
	input, _ := reader.ReadString('\n')
	return strings.TrimSpace(strings.ToLower(input)) == "y"
}
