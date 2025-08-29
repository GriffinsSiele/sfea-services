FROM golang:1.22.0-alpine AS builder
WORKDIR /app
COPY go.mod go.sum ./
RUN go mod download
COPY . .
RUN go build -o /bin/healthcheck

FROM alpine:latest
RUN apk add --no-cache ca-certificates
COPY --from=builder /bin/healthcheck /bin/healthcheck
EXPOSE 8000
ENTRYPOINT [ "/bin/healthcheck" ]
