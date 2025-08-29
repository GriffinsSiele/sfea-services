package config

import "strings"

type EntryNodes string

func (n EntryNodes) List() []string {
	return list(string(n))
}

type ExitNodes string

func (n ExitNodes) List() []string {
	return list(string(n))
}

type ExcludeNodes string

func (n ExcludeNodes) List() []string {
	return list(string(n))
}

func list(s string) []string {
	if s == "" {
		return nil
	}
	return strings.Split(s, ",")
}

type PoolSize int

type MaxMemInQueuesMB int
