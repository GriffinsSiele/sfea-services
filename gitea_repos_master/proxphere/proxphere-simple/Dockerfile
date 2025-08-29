FROM golang:1.22.4-alpine
WORKDIR /app
COPY go.mod go.sum ./
RUN go mod tidy
COPY . ./
RUN go build -o bin/app
EXPOSE 1080
ENTRYPOINT [ "./bin/app" ]
