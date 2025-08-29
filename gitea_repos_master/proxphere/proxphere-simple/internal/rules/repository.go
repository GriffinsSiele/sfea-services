package rules

import (
	"context"
	"errors"
	"fmt"
	"os"
	"path/filepath"

	"gopkg.in/yaml.v3"
)

type Repository struct {
	rules []*Rule
}

func NewRepository() (*Repository, error) {
	dir := "config/rules"
	rules := make([]*Rule, 0)

	entries, err := os.ReadDir(dir)
	if err != nil {
		return nil, fmt.Errorf("failed to read rules directory: %w", err)
	}

	for _, entry := range entries {
		if entry.IsDir() {
			continue
		}

		if filepath.Ext(entry.Name()) != ".yaml" {
			continue
		}

		filename := filepath.Join(dir, entry.Name())
		file, err := os.Open(filename)
		if err != nil {
			return nil, fmt.Errorf("failed to open file: %s: %w", filename, err)
		}
		//goland:noinspection GoUnhandledErrorResult,GoDeferInLoop
		defer file.Close()

		fileRules := make([]*Rule, 0)
		if err = yaml.NewDecoder(file).Decode(&fileRules); err != nil {
			return nil, fmt.Errorf("failed to decode file: %s: %w", filename, err)
		}

		rules = append(rules, fileRules...)
	}

	return &Repository{rules: rules}, nil
}

func (r *Repository) FindOneByUsernameAndPassword(_ context.Context, username, password string) (*Rule, error) {
	for _, rule := range r.rules {
		if rule.Username == username && rule.Password == password {
			return rule, nil
		}
	}

	return nil, errors.New("no rule found")
}
