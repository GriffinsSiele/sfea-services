FROM golang:1.21-alpine AS build
WORKDIR /app
ADD . ./

FROM build AS production-build
RUN go build -mod=vendor -o ./bin/console .

FROM alpine AS production
WORKDIR /app
COPY ./var /app/var
COPY --from=production-build /app/bin/console /app/bin/console
EXPOSE 3000
ENTRYPOINT [ "bin/console" ]

FROM build AS development
ENTRYPOINT [ "go", "run", "." ]
