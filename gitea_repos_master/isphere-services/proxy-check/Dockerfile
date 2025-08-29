FROM python:3.6.9-slim

WORKDIR /opt/proxy-check/src

RUN apt update && \
    apt install -y supervisor

RUN pip install aiohttp aiohttp_proxy

COPY config/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

COPY public/. .

CMD supervisord -c /etc/supervisor/conf.d/supervisord.conf
