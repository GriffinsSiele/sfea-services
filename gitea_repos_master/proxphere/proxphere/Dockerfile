FROM golang:1.22-alpine AS build
WORKDIR /app
RUN apk add curl nss
ADD . ./

FROM build AS production-build
RUN go build -mod=vendor -o ./bin/console .

FROM alpine AS production
RUN apk add curl nss tzdata
WORKDIR /app
COPY --from=production-build /app/.env /app/.env
COPY --from=production-build /app/bin/console /app/bin/console
EXPOSE 1080
EXPOSE 80
EXPOSE 443
ENTRYPOINT [ "bin/console" ]

FROM build AS development
EXPOSE 1080
EXPOSE 80
EXPOSE 443
ENTRYPOINT [ "go", "run", "." ]
