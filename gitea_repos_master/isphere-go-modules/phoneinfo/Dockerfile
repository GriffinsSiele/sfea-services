FROM golang:alpine AS compiler
WORKDIR /app
ADD . ./
RUN go build -mod=vendor -o /usr/local/bin/phoneinfo

FROM scratch
COPY --from=compiler /usr/local/bin/phoneinfo /usr/local/bin/phoneinfo
EXPOSE 80
ENTRYPOINT [ "/usr/local/bin/phoneinfo" ]
