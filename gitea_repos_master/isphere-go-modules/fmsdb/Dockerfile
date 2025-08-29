FROM golang:1.20-alpine AS build
WORKDIR /app
ADD . ./

FROM build AS production-build
RUN go build -mod=vendor -o ./bin/console .

FROM alpine AS production
WORKDIR /app
RUN apk add p7zip
COPY --from=production-build /app/.env /app/.env
COPY --from=production-build /app/bin/console /app/bin/console
EXPOSE 3000
ENTRYPOINT [ "bin/console", "http:serve" ]

FROM build AS development
