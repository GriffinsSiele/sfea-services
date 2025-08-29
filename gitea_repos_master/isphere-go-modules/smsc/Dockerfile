FROM golang:alpine AS compiler
WORKDIR /app
ADD . ./
RUN go build -mod=vendor -o /usr/local/bin/smsc

FROM alpine
COPY --from=compiler /usr/local/bin/smsc /usr/local/bin/smsc
EXPOSE 80
ENTRYPOINT [ "/usr/local/bin/smsc" ]
