FROM golang:1.22.5-alpine AS dependencies
WORKDIR /app
COPY go.mod go.sum ./
RUN go mod download

FROM dependencies AS compiler
WORKDIR /app
COPY . ./
RUN go build -o ./build/tcp-firewall ./cmd/tcp-firewall/...

FROM scratch AS tcp-firewall
WORKDIR /app
COPY --from=compiler /app/build/tcp-firewall /app/build/tcp-firewall
EXPOSE 443
CMD [ "/app/build/tcp-firewall" ]
