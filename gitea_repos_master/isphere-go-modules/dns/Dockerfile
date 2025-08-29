FROM golang:1.20-alpine AS build
WORKDIR /app
ADD . ./
RUN go build -mod=vendor -o ./bin/console .

FROM scratch
WORKDIR /app
COPY --from=build /app/.env /app/.env
COPY --from=build /app/bin/console /app/bin/console
EXPOSE 3000
ENTRYPOINT [ "bin/console" ]
