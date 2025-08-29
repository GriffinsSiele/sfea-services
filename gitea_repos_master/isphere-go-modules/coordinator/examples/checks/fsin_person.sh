#!/bin/sh
go run . invoke --scope fsin_person -- \
  --last_name ДОЛГОВ \
  --first_name АЛЕКСАНДР \
  --patronymic ЕВГЕНЬЕВИЧ \
  --date 1998-05-25
