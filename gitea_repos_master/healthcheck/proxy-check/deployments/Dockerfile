FROM golang:1.22.5-alpine AS dependencies
WORKDIR /app
COPY go.mod go.sum ./
RUN go mod download

FROM dependencies AS compiler
WORKDIR /app
COPY . ./
RUN go build -o ./build/proxy-check ./cmd/proxy-check/...

FROM alpine AS proxy-check
WORKDIR /app
COPY --from=compiler /app/build/proxy-check /app/build/proxy-check
COPY .env /app/.env
CMD [ "/app/build/proxy-check" ]
