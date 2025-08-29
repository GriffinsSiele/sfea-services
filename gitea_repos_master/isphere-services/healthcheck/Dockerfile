FROM golang:1.21-alpine AS build
RUN apk add go pkgconfig openssl openssl-dev # extra build libs
WORKDIR /app
COPY go.mod go.sum ./
RUN go mod download
ADD . ./

FROM build AS production-build
RUN go build -mod=vendor -o ./bin/console .

FROM alpine AS production
RUN apk add go pkgconfig openssl openssl-dev # extra build libs
WORKDIR /app
COPY --from=production-build /app/bin/console /app/bin/console
EXPOSE 8000
ENTRYPOINT [ "bin/console" ]

FROM build AS development
ENTRYPOINT [ "go", "run", "." ]
