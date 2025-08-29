FROM golang:1.21-alpine AS build
WORKDIR /app
ADD . ./

FROM build AS production-build
RUN go build -mod=vendor -o ./bin/console .

FROM alpine AS production
WORKDIR /app
COPY --from=production-build /app/bin/console /app/bin/console
ENTRYPOINT [ "bin/console" ]

FROM build AS development
ENTRYPOINT [ "go", "run", "." ]
