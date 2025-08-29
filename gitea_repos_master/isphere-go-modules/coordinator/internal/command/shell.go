package command

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"net/http"
	"os"
	"path"
	"sort"
	"time"

	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/config"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/contract"
	"gitea-http.gitea.svc.cluster.local/isphere-go-modules/coordinator/internal/manager"
	"github.com/charmbracelet/huh"
	"github.com/urfave/cli/v2"
	"gopkg.in/yaml.v2"
)

type ShellCommand struct {
	cfg              *config.Config
	checkTypeManager *manager.CheckTypeManager
}

func NewShellCommand(cfg *config.Config, checkTypeManager *manager.CheckTypeManager) *ShellCommand {
	return &ShellCommand{
		cfg:              cfg,
		checkTypeManager: checkTypeManager,
	}
}

func (t *ShellCommand) Describe() *cli.Command {
	return &cli.Command{
		Category: "shell",
		Name:     "shell",
		Action:   t.Action,
	}
}

func (t *ShellCommand) Action(c *cli.Context) error {
	checkType, err := t.selectCheckType()
	if err != nil {
		return fmt.Errorf("failed to select check type: %w", err)
	}

	schema, err := t.explodeSchema(checkType.Schema)
	if err != nil {
		return fmt.Errorf("failed to explode schema: %w", err)
	}

	properties, ok := schema["properties"].(map[string]any)
	if !ok {
		return fmt.Errorf("expected schema to have properties")
	}

	var (
		inputs = make([]huh.Field, 0)
		values = make(map[string]*string)
	)
	for name, property := range properties {
		if property.(map[string]any)["format"] == "hidden" {
			continue
		}
		var value string
		values[name] = &value
		input := huh.NewInput().Title(name).Inline(true).Value(&value)
		huh.NewInput()
		inputs = append(inputs, input)
	}
	form := huh.NewForm(huh.NewGroup(inputs...))
	if err := form.Run(); err != nil {
		return fmt.Errorf("failed to render form: %w", err)
	}

	data := map[string]any{
		"id":        1,
		"key":       "",
		"starttime": time.Now().Unix(),
	}
	for k, v := range values {
		if v != nil {
			data[k] = *v
		}
	}
	serialized, err := json.Marshal(data)
	if err != nil {
		return fmt.Errorf("failed to serialize form values: %w", err)
	}

	ctx := c.Context
	ctx = context.WithValue(ctx, contract.CacheControlNoCache, new(any))

	response, err := t.checkTypeManager.Apply(ctx, checkType, bytes.NewReader(serialized))
	if err != nil {
		return fmt.Errorf("failed to apply check type: %w", err)
	}

	fmt.Printf("HTTP/1.1 %d %s\n", response.Code, http.StatusText(response.Code))
	fmt.Printf("Server: iSphere-Shell/1.0.0\n")
	fmt.Printf("Content-Type: application/yaml\n")
	fmt.Println()
	enc := yaml.NewEncoder(os.Stdout)
	if err := enc.Encode(response.Records); err != nil {
		return fmt.Errorf("failed to decode response: %w", err)
	}
	fmt.Println()

	return nil
}

func (t *ShellCommand) selectCheckType() (*config.CheckType, error) {
	var names []string
	for name, checkType := range t.cfg.CheckTypes {
		if checkType.Enabled {
			names = append(names, name)
		}
	}
	sort.Strings(names)

	options := make([]huh.Option[string], len(names))
	for i, checkTypeName := range names {
		options[i] = huh.NewOption(checkTypeName, checkTypeName)
	}

	var selectedName string
	checkTypeForm := huh.NewForm(
		huh.NewGroup(
			huh.NewSelect[string]().
				Title("Select a check type").
				Options(options...).
				Value(&selectedName),
		),
	)
	if err := checkTypeForm.Run(); err != nil {
		return nil, fmt.Errorf("failed to render form")
	}

	checkType, ok := t.cfg.CheckTypes[selectedName]
	if !ok {
		return nil, fmt.Errorf("unknown check type: %s", selectedName)
	}

	return checkType, nil
}

func (t *ShellCommand) explodeSchema(schema map[string]any) (map[string]any, error) {
	result := make(map[string]any)
	for propertyPath, property := range schema {
		switch propertyPath {
		case "allOf":
			propertySlice, ok := property.([]any)
			if !ok {
				return nil, fmt.Errorf("expected allOf to be an array")
			}
			res := map[string]any{}
			for _, schema := range propertySlice {
				schemaMap, ok := schema.(map[string]any)
				if !ok {
					return nil, fmt.Errorf("expected allOf to be an array of objects")
				}
				schema, err := t.explodeSchema(schemaMap)
				if err != nil {
					return nil, fmt.Errorf("failed to explode schema: %w", err)
				}
				mergeMaps(schema, res)
			}
			result = res
		case "$ref":
			propertyStr, ok := property.(string)
			if !ok {
				return nil, fmt.Errorf("expected $ref to be a string")
			}
			definition, err := t.cfg.GetDefinitionByName(path.Base(propertyStr))
			if err != nil {
				return nil, fmt.Errorf("cannot get definition: %w", err)
			}
			result = definition
		default:
			result[propertyPath] = property
		}
	}
	return result, nil
}

func mergeMaps(source, destination map[string]any) {
	for key, sourceValue := range source {
		destinationValue, ok := destination[key]
		if !ok {
			destination[key] = sourceValue
			continue
		}

		switch sourceMap := sourceValue.(type) {
		case map[string]any:
			if destinationMap, ok := destinationValue.(map[string]any); ok {
				mergeMaps(sourceMap, destinationMap)
			}
		case []any:
			if destinationSlice, ok := destinationValue.([]any); ok {
				mergedSlice := append(sourceMap, destinationSlice...)
				destination[key] = mergedSlice
			}
		default:
			destination[key] = sourceValue
		}
	}
}
