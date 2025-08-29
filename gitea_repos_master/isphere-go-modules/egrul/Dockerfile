FROM golang:1.22.3-alpine AS build
WORKDIR /app
ADD . ./

FROM build AS production-build
RUN go build -mod=vendor -o ./bin/console .

FROM alpine AS production
WORKDIR /app
COPY --from=production-build /app/config/hierarchy-template.yaml /app/config/hierarchy-template.yaml
COPY --from=production-build /app/.env /app/.env
COPY --from=production-build /app/bin/console /app/bin/console
EXPOSE 8000
ENTRYPOINT [ "bin/console" ]

FROM build AS development
ENTRYPOINT [ "go", "run", "." ]
