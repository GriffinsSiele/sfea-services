package command

import (
	"context"
	"crypto/md5"
	"encoding/hex"
	"encoding/json"
	"errors"
	"fmt"
	"net/http"
	"net/url"
	"os"
	"regexp"
	"sort"
	"strconv"
	"strings"
	"time"

	"github.com/AlecAivazis/survey/v2"
	"github.com/c-bata/go-prompt"
	"github.com/davecgh/go-spew/spew"
	amqp "github.com/rabbitmq/amqp091-go"
	"github.com/redis/go-redis/v9"
	"github.com/sirupsen/logrus"
	"github.com/urfave/cli/v2"
	"gopkg.in/yaml.v2"
)

type ClientCommand struct {
	State *ClientCommandState
}

func NewClientCommand() *ClientCommand {
	return &ClientCommand{
		State: NewClientCommandState(),
	}
}

func (t *ClientCommand) Describe() *cli.Command {
	return &cli.Command{
		Category: "clients",
		Name:     "clients:shell",
		Action:   t.Action,
		Flags: cli.FlagsByName{
			&cli.StringFlag{
				Name:  "rabbitmq-dsn",
				Value: "amqp://default_user_vlhc67EfKTm49j6RKwF:sGSMvY9yOBEVibxt71n0x4h0E4g_fYvo@i-sphere.rabbitmq:5672/",
			},
			&cli.StringFlag{
				Name:  "keydb-dsn",
				Value: "redis://:n1vTY76fuCT59MH@keydb.keydb:6379/0",
			},
			&cli.StringFlag{
				Name:  "coordinator-url",
				Value: "http://coordinator-master-http-serve.isphere-go-modules",
			},
		},
	}
}

func (t *ClientCommand) Action(c *cli.Context) error {
	// rabbitmq
	rabbitMQConn, rabbitMQChannel, err := t.connectToRabbitMQ(c)
	if err != nil {
		return fmt.Errorf("failed to connect to RabbitMQ: %w", err)
	}

	defer func() {
		if err := rabbitMQChannel.Close(); err != nil {
			logrus.WithError(err).Error("failed to close RabbitMQ channel")
		}

		if err := rabbitMQConn.Close(); err != nil {
			logrus.WithError(err).Error("failed to close RabbitMQ connection")
		}
	}()

	// keydb
	keyDBConn, err := t.connectToKeyDB(c)
	if err != nil {
		return fmt.Errorf("failed to connect to KeyDB: %w", err)
	}

	defer func() {
		if err := keyDBConn.Close(); err != nil {
			logrus.WithError(err).Error("failed to close KeyDB connection")
		}
	}()

	// shell
	hostname, err := os.Hostname()
	if err != nil {
		return fmt.Errorf("failed to get hostname: %w", err)
	}

	var (
		lsRe     = regexp.MustCompile(`^ls$`)
		useRe    = regexp.MustCompile(`^use(?: (\w+))?$`)
		setRe    = regexp.MustCompile(`^set (\w+) (.+)$`)
		unsetRe  = regexp.MustCompile(`^unset (\w+)$`)
		envRe    = regexp.MustCompile(`^env$`)
		startRe  = regexp.MustCompile(`^start$`)
		runRe    = regexp.MustCompile(`^run$`)
		statusRe = regexp.MustCompile(`^status$`)
	)

	logrus.Info("Please use `Ctrl-D` to exit this shell")

	prompter := prompt.New(
		func(execution string) {
			switch {
			case lsRe.MatchString(execution):
				t.lsCmd(c)
			case useRe.MatchString(execution):
				t.useCmd(c, useRe, execution)
			case setRe.MatchString(execution):
				t.setCmd(setRe, execution)
			case unsetRe.MatchString(execution):
				t.unsetCmd(unsetRe, execution)
			case envRe.MatchString(execution):
				t.envRe()
			case startRe.MatchString(execution):
				t.startCmd(c.Context, rabbitMQChannel)
			case runRe.MatchString(execution):
				t.runCmd(c.Context, rabbitMQChannel, keyDBConn)
			case statusRe.MatchString(execution):
				t.statusCmd(c.Context, keyDBConn)
			default:
				logrus.WithField("execution", execution).Error("command not found")
			}
		},
		func(d prompt.Document) []prompt.Suggest { return nil },
		prompt.OptionTitle("isphere-prompt: i-sphere interactive clients"),
		prompt.OptionPrefix(fmt.Sprintf("i-sphere@%s:~$ ", hostname)),
		prompt.OptionInputTextColor(prompt.DarkGray),
	)

	prompter.Run()

	return nil
}

func (t *ClientCommand) connectToRabbitMQ(c *cli.Context) (*amqp.Connection, *amqp.Channel, error) {
	logrus.Debug("connecting to RabbitMQ")

	conn, err := amqp.Dial(c.String("rabbitmq-dsn"))
	if err != nil {
		return nil, nil, fmt.Errorf("failed to dial RabbitMQ: %w", err)
	}

	channel, err := conn.Channel()
	if err != nil {
		return nil, nil, fmt.Errorf("failed to open RabbitMQ channel: %w", err)
	}

	return conn, channel, nil
}

func (t *ClientCommand) connectToKeyDB(c *cli.Context) (*redis.Conn, error) {
	logrus.Debug("connecting to KeyDB")

	dsn, err := url.Parse(c.String("keydb-dsn"))
	if err != nil {
		return nil, fmt.Errorf("failed to parse KeyDB dsn: %w", err)
	}

	password, ok := dsn.User.Password()
	if !ok {
		return nil, fmt.Errorf("failed to parse KeyDB password from KeyDB DSN")
	}

	database, err := strconv.Atoi(strings.TrimPrefix(dsn.Path, "/"))
	if err != nil {
		return nil, fmt.Errorf("failed to parse KeyDB database: %w", err)
	}

	conn := redis.NewClient(&redis.Options{
		Addr:         dsn.Host,
		Password:     password,
		DB:           database,
		ReadTimeout:  -1,
		WriteTimeout: -1,
	})

	pong, err := conn.Ping(c.Context).Result()
	if err != nil {
		return nil, fmt.Errorf("failed to ping KeyDB: %w", err)
	}

	if pong != "PONG" {
		return nil, fmt.Errorf("unexpected KeyDB response, expected `PONG` but actual `%s`", pong)
	}

	return conn.Conn(), err
}

func (t *ClientCommand) loadCheckTypes(c *cli.Context) ([]*CheckType, error) {
	logrus.Debug("loading check types")

	resp, err := http.Get(c.String("coordinator-url") + "/api/v1/check-types")
	if err != nil {
		return nil, fmt.Errorf("failed to load check types: %w", err)
	}

	defer func() {
		if err := resp.Body.Close(); err != nil {
			logrus.WithError(err).Error("failed to close response body")
		}
	}()

	checkTypes := make([]*CheckType, 0)
	if err := json.NewDecoder(resp.Body).Decode(&checkTypes); err != nil {
		return nil, fmt.Errorf("failed to unmarshal check types: %w", err)
	}

	return checkTypes, nil
}

func (t *ClientCommand) lsCmd(c *cli.Context) {
	checkTypeCodes, err := t.getCheckTypeCodes(c)
	if err != nil {
		logrus.WithError(err).Fatal("failed to get check type codes")
	}

	for _, code := range checkTypeCodes {
		_, _ = spew.Println(code)
	}
}

func (t *ClientCommand) getCheckTypeCodes(c *cli.Context) ([]string, error) {
	checkTypes, err := t.loadCheckTypes(c)
	if err != nil {
		return nil, fmt.Errorf("failed to load check types")
	}

	checkTypeCodes := make([]string, 0, len(checkTypes))
	for _, checkType := range checkTypes {
		checkTypeCodes = append(checkTypeCodes, checkType.Code)
	}

	sort.Strings(checkTypeCodes)

	return checkTypeCodes, nil
}

func (t *ClientCommand) useCmd(c *cli.Context, re *regexp.Regexp, execution string) {
	var (
		match = re.FindStringSubmatch(execution)
		code  string
	)

	if match[1] == "" {
		checkTypeCodes, err := t.getCheckTypeCodes(c)
		if err != nil {
			logrus.WithError(err).Fatal("failed to get check type codes")
		}

		sel := &survey.Select{
			Message: "Select a check type",
			Options: checkTypeCodes,
		}

		if err = survey.AskOne(sel, &code); err != nil {
			logrus.WithError(err).Errorf("failed to ask check type")

			return
		}
	} else {
		code = match[1]
	}

	checkTypes, err := t.loadCheckTypes(c)
	if err != nil {
		logrus.WithError(err).Errorf("failed to load check types")

		return
	}

	for _, checkType := range checkTypes {
		if checkType.Code == code {
			t.State.UsedCheckType = checkType
			t.State.Reset()

			return
		}
	}

	logrus.WithField("code", code).Error("no check type found")
}

func (t *ClientCommand) setCmd(re *regexp.Regexp, execution string) {
	t.assertUsedCheckTypeIsSet()

	var (
		match      = re.FindStringSubmatch(execution)
		serialized = fmt.Sprintf("field: %s", match[2])
		obj        = make(map[string]any)
	)

	if err := yaml.Unmarshal([]byte(serialized), &obj); err != nil {
		logrus.WithError(err).Error("unreachable expression: %w", err)
	}

	t.State.Input[match[1]] = obj["field"]

	logrus.WithFields(logrus.Fields{
		"code":  t.State.UsedCheckType.Code,
		"key":   match[1],
		"value": obj["field"],
	}).Debug("set session value")
}

func (t *ClientCommand) unsetCmd(re *regexp.Regexp, execution string) {
	t.assertUsedCheckTypeIsSet()

	match := re.FindStringSubmatch(execution)

	delete(t.State.Input, match[1])

	logrus.WithFields(logrus.Fields{
		"code": t.State.UsedCheckType.Code,
		"key":  match[1],
	}).Debug("unset session value")
}

func (t *ClientCommand) envRe() {
	t.assertUsedCheckTypeIsSet()

	keys := make([]string, 0, len(t.State.Input))
	for key := range t.State.Input {
		keys = append(keys, key)
	}

	sort.Strings(keys)

	for _, key := range keys {
		logrus.WithFields(logrus.Fields{
			"code":  t.State.UsedCheckType.Code,
			"key":   key,
			"value": t.State.Input[key],
		}).Info("show session value")
	}
}

func (t *ClientCommand) startCmd(ctx context.Context, ch *amqp.Channel) {
	t.assertUsedCheckTypeIsSet()

	// build hash
	keys := make([]string, 0)
	for key := range t.State.Input {
		keys = append(keys, key)
	}

	sort.Strings(keys)

	sb := strings.Builder{}
	for _, key := range keys {
		sb.WriteString(fmt.Sprintf("%v", t.State.Input[key]))
	}

	hash := md5.Sum([]byte(sb.String()))
	t.State.Input["key"] = hex.EncodeToString(hash[:])

	// rest fields
	t.State.Input["id"] = 1
	t.State.Input["starttime"] = time.Now().Unix()

	// check the required exchange
	var (
		exchangeParts = strings.Split(t.State.UsedCheckType.Links.Queue, ":")
		exchange      = exchangeParts[len(exchangeParts)-1]
	)

	// prepare publishing
	serialized, err := json.Marshal(t.State.Input)

	if err != nil {
		logrus.WithError(err).Error("failed to marshal input")

		return
	}

	publishing := amqp.Publishing{
		Headers: amqp.Table{"x-request-id": t.State.Input["key"]},
		Body:    serialized,
	}

	// publish message
	if err := ch.PublishWithContext(ctx, exchange, "", false, false, publishing); err != nil {
		logrus.WithError(err).Error("failed to publish message")

		return
	}

	t.State.Key = t.State.Input["key"].(string)

	logrus.WithFields(logrus.Fields{
		"code":    t.State.UsedCheckType.Code,
		"scope":   exchange,
		"headers": publishing.Headers,
		"body":    string(serialized),
		"key":     t.State.Key,
	}).Info("published message")
}

func (t *ClientCommand) runCmd(ctx context.Context, ch *amqp.Channel, conn *redis.Conn) {
	t.startCmd(ctx, ch)

}

func (t *ClientCommand) statusCmd(ctx context.Context, conn *redis.Conn) {
	t.assertUsedCheckTypeIsSet()

	var (
		collectionParts = strings.Split(t.State.UsedCheckType.Links.Storage, ":")
		collection      = collectionParts[len(collectionParts)-1]
	)

	resp, err := conn.HGet(ctx, collection, t.State.Key).Result()
	if err != nil {
		if !errors.Is(err, redis.Nil) {
			logrus.WithError(err).Error("failed to hget")

			return
		}
	}

	logrus.WithFields(logrus.Fields{
		"code":  t.State.UsedCheckType.Code,
		"scope": collection,
		"key":   t.State.Key,
	}).Debug("received status")

	if resp == "" {
		logrus.Error("no result found")

		return
	}

	data := make(map[string]any)

	if err := json.Unmarshal([]byte(resp), &data); err != nil {
		logrus.WithError(err).Error("failed to unmarshal response data")

		return
	}

	serialized, err := json.MarshalIndent(data, "", "  ") // for indent

	if err != nil {
		logrus.WithError(err).Error("failed to reverse serialize response data")

		return
	}

	_, _ = spew.Println(string(serialized))
}

func (t *ClientCommand) assertUsedCheckTypeIsSet() {
	if t.State.UsedCheckType == nil {
		logrus.Warn("check type is not set, please enter for example `use geoip` before")
	}
}

type ClientCommandState struct {
	CheckTypes    []*CheckType
	UsedCheckType *CheckType
	Input         map[string]any
	Key           string
}

func NewClientCommandState() *ClientCommandState {
	return &ClientCommandState{
		CheckTypes: make([]*CheckType, 0),
		Input:      make(map[string]any),
		Key:        "",
	}
}

func (t *ClientCommandState) Reset() {
	t.Input = make(map[string]any)
	t.Key = ""
}

type CheckType struct {
	Code   string `json:"code"`
	Schema struct {
		Consume map[string]any `json:"consume"`
		Produce map[string]any `json:"produce"`
	} `json:"@schema"`
	Links struct {
		Queue   string `json:"queue"`
		Storage string `json:"storage"`
	} `json:"@links"`
}
