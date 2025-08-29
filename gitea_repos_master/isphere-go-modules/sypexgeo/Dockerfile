FROM golang:alpine3.18 AS compiler
WORKDIR /app
RUN apk add ca-certificates
ADD . ./
RUN go build -mod=vendor -o /usr/local/bin/sypexgeo

FROM scratch
COPY --from=compiler /etc/ssl/certs/ca-certificates.crt /etc/ssl/certs/
COPY --from=compiler /usr/local/bin/sypexgeo /usr/local/bin/sypexgeo
EXPOSE 80
ENTRYPOINT [ "/usr/local/bin/sypexgeo" ]
