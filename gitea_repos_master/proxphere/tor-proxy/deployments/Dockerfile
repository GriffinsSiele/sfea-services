FROM golang:alpine AS base
WORKDIR /app
COPY ./go.mod ./go.sum ./
RUN go mod download

FROM base AS compiler
WORKDIR /app
COPY ./. ./.
RUN go build -o build/tor-proxy ./cmd/tor-proxy/...

FROM alpine AS production
RUN apk add tor
WORKDIR /app
COPY --from=compiler /app/build/tor-proxy /app/build/tor-proxy
COPY configs/ ./configs/
ENTRYPOINT [ "/app/build/tor-proxy" ]

FROM base AS development
RUN apk add tor
WORKDIR /app
COPY ./. ./.
ENTRYPOINT [ "go", "run", "./cmd/tor-proxy/..." ]
