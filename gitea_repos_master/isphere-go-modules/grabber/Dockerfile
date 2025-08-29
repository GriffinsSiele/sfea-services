FROM golang:1.20-alpine AS binaries
RUN go install github.com/pressly/goose/v3/cmd/goose@latest

FROM golang:1.20-alpine AS build
WORKDIR /app
ADD . ./

FROM build AS production-build
RUN go mod download
RUN go build -o ./bin/console

FROM alpine AS production
WORKDIR /app
RUN apk add poppler-utils
COPY --from=binaries /go/bin/goose /usr/local/bin/goose
COPY --from=production-build /app/bin/console /app/bin/console
COPY --from=production-build /app/.env /app/.env
COPY --from=production-build /app/migrations /app/migrations

FROM build AS development
