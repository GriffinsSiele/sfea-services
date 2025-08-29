#!/bin/sh
go run . invoke --scope fmsdb_passport -- \
  --passport_series 1::int \
  --passport_number 162185::int
