FROM golang:alpine AS compiler
WORKDIR /app
ADD . ./
RUN go build -mod=vendor -o /usr/local/bin/ripe

FROM scratch
COPY --from=compiler /usr/local/bin/ripe /usr/local/bin/ripe
EXPOSE 80
ENTRYPOINT [ "/usr/local/bin/ripe" ]
