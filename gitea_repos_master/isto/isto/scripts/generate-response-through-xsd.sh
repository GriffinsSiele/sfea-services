#!/bin/sh
set -ex
# go install github.com/xuri/xgen/cmd/xgen@latest
xsd_source_url=https://i-sphere.ru/2.00/response.xsd
curl -o /tmp/response.xsd "${xsd_source_url}"
mkdir -p pkg/models/main-service/response/schema
xgen -i /tmp/response.xsd -o ./pkg/models/main-service/response/schema/response -l Go
rm /tmp/response.xsd
