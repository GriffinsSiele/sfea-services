FROM php:7.2.34

WORKDIR /var/www/crontab/sourcesAlert

RUN apt update && \
    apt install -y supervisor && \
    docker-php-ext-install -j"$(nproc)" mysqli 

COPY config/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

COPY public/* ./

CMD supervisord -c /etc/supervisor/conf.d/supervisord.conf
