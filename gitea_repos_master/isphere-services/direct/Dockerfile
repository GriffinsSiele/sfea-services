FROM golang:1.21-alpine AS build
RUN apk add go pkgconfig openssl openssl-dev # extra build libs
WORKDIR /app
ADD . ./

FROM build AS production-build
RUN go build -mod=vendor -o ./bin/console .

FROM alpine AS production
RUN apk add go pkgconfig openssl openssl-dev # extra build libs
WORKDIR /app
COPY --from=production-build /app/bin/console /app/bin/console
COPY --from=production-build /app/.env /app/.env
EXPOSE 53
ENTRYPOINT [ "bin/console" ]

FROM build AS development
ENTRYPOINT [ "go", "run", "." ]
