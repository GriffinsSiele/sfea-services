FROM golang:1.20-alpine AS build
RUN apk add git make vim
WORKDIR /app
ADD . ./

FROM build AS prod-build
RUN go mod vendor && go build -o ./bin/console

FROM alpine AS prod
RUN apk add git
WORKDIR /app
COPY --from=prod-build /app/.env /app/.env
COPY --from=prod-build /app/bin/console /app/bin/console
ENTRYPOINT [ "/app/bin/console" ]

FROM build AS dev
RUN apk add vim
ENTRYPOINT [ "go", "run", "/app/."]
