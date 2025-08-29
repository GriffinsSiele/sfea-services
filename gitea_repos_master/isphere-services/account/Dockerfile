FROM golang:1.20-alpine AS build
RUN apk add git make vim
WORKDIR /app
ADD . ./

FROM build AS production-build
RUN go mod vendor && go build -o ./bin/console

FROM alpine AS production
RUN apk add git
WORKDIR /app
COPY --from=production-build /app/.env /app/.env
COPY --from=production-build /app/bin/console /app/bin/console
COPY --from=production-build /app/templates /app/templates
ENTRYPOINT [ "/app/bin/console", "http:serve", "--port", "80" ]

FROM build AS development
RUN apk add vim
ENTRYPOINT [ "go", "run", "/app/.", "http:serve" ]
