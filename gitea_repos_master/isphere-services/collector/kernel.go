package main

import "fmt"

type Kernel struct {
	container *Container
}

func NewKernel(container *Container) *Kernel {
	return &Kernel{
		container: container,
	}
}

func (t *Kernel) Build() error {
	if err := t.container.Initialize(); err != nil {
		return fmt.Errorf("container initialize: %w", err)
	}

	return nil
}
