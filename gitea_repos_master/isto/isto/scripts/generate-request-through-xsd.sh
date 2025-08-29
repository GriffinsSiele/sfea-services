#!/bin/sh
set -ex
# go install github.com/xuri/xgen/cmd/xgen@latest
xsd_source_url=https://i-sphere.ru/2.00/request.xsd
curl -o /tmp/request.xsd "${xsd_source_url}"
mkdir -p pkg/models/main-service/request/schema
xgen -i /tmp/request.xsd -o ./pkg/models/main-service/request/schema/request -l Go
rm /tmp/request.xsd
