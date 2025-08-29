FROM golang:alpine AS base
WORKDIR /app
COPY ./go.mod ./go.sum ./
RUN go mod download

FROM base AS compiler
WORKDIR /app
COPY ./. ./.
RUN go build -o build/isto ./cmd/isto/...

FROM alpine AS production
WORKDIR /app
COPY --from=compiler /app/build/isto /app/build/isto
COPY .env /app/.env
ENTRYPOINT [ "/app/build/isto" ]

FROM base AS development
WORKDIR /app
COPY ./. ./.
ENTRYPOINT [ "go", "run", "./cmd/isto/..." ]
