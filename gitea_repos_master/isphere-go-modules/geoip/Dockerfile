FROM alpine AS geolite2
ARG GEOLITE2_GRAB_VER=2024.08.28
RUN apk add wget ; \
    mkdir -p /opt/GeoLite2 ; \
    wget -P /opt/GeoLite2 https://github.com/P3TERX/GeoLite.mmdb/releases/download/${GEOLITE2_GRAB_VER}/GeoLite2-City.mmdb

FROM golang:alpine AS compiler
WORKDIR /app
ADD . ./
RUN go build -mod=vendor -o /usr/local/bin/geoip

FROM scratch
COPY --from=geolite2 /opt/GeoLite2/GeoLite2-City.mmdb /var/lib/GeoLite2/GeoLite2-City.mmdb
COPY --from=compiler /usr/local/bin/geoip /usr/local/bin/geoip
EXPOSE 80
ENTRYPOINT [ "/usr/local/bin/geoip" ]
