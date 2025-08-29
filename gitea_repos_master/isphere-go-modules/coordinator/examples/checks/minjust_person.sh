#!/bin/sh
go run . invoke --scope minjust_person -- \
  --last_name ШУКАЕВА \
  --first_name ЕЛЕНА \
  --patronymic ВИКТОРОВНА \
  --date 1968-09-23
