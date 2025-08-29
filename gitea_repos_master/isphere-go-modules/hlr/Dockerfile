FROM golang:alpine AS compiler
WORKDIR /app
ADD . ./
RUN apk add ca-certificates
RUN go build -mod=vendor -o /usr/local/bin/hlr

FROM alpine
ENV USER=nobody
COPY --from=compiler /etc/ssl/certs/ca-certificates.crt /etc/ssl/certs/
COPY --from=compiler /usr/local/bin/hlr /usr/local/bin/hlr
EXPOSE 80
ENTRYPOINT [ "/usr/local/bin/hlr" ]
