package main

import (
	"go.uber.org/dig"
)

type Kernel struct {
	*dig.Container
}

func NewKernel(container *dig.Container) *Kernel {
	return &Kernel{
		container,
	}
}

func (t *Kernel) Build() error {
	return nil
}
