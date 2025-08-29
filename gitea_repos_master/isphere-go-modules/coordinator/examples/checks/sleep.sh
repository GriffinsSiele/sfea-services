#!/bin/sh
go run . invoke --scope sleep -- \
  --seconds 2::int
