package shell

import (
	"context"
	"fmt"
	"os/exec"
)

type Conn struct {
	*Client
}

func (c *Conn) Release() {
}

func (c *Conn) Exec(ctx context.Context, query string, res any, _ map[string]any) error {
	cmd := exec.CommandContext(ctx, "/bin/sh", "-c", query)

	stdout, err := cmd.Output()
	if err != nil {
		return fmt.Errorf("failed to execute command: %w", err)
	}

	resObj, _ := res.(**map[string]any)
	(**resObj)["stdout"] = string(stdout)

	return nil
}
