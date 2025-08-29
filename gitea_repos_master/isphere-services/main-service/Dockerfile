ARG BUILD_TARGET


FROM php:8.2-fpm-alpine AS base

COPY --from=composer:2.5.5 /usr/bin/composer /usr/local/bin/composer
COPY --from=surnet/alpine-wkhtmltopdf:3.16.2-0.12.6-full /bin/wkhtmltopdf /bin/libwkhtmltox.so /usr/local/bin/

RUN apk add \
      --no-cache  \
      --virtual \
      .phpize-deps \
      autoconf \
      dpkg \
      dpkg-dev \
      file \
      g++ \
      gcc \
      libc-dev \
      linux-headers \
      make \
      pkgconf \
      re2c \
    && apk add \
      --no-cache \
      bash \
      ca-certificates \
      curl-dev \
      font-droid \
      font-freefont \
      font-liberation \
      fontconfig \
      freetype \
      git \
      icu-dev \
      libgcrypt-dev \
      libmemcached-dev \
      libpng-dev \
      libssl1.1 \
      libstdc++ \
      libx11 \
      libxext \
      libxrender \
      libxslt-dev \
      libzip-dev \
      php81-iconv \
      rabbitmq-c \
      rabbitmq-c-dev \
      vim \
      xvfb-run \
      zlib-dev

RUN set -ex ; \
    docker-php-ext-install -j"$(nproc)" ctype ; \
    docker-php-ext-install -j"$(nproc)" gd ; \
    docker-php-ext-install -j"$(nproc)" intl ; \
    docker-php-ext-install -j"$(nproc)" opcache ; \
    docker-php-ext-install -j"$(nproc)" pdo ; \
    docker-php-ext-install -j"$(nproc)" pdo_mysql ; \
    docker-php-ext-install -j"$(nproc)" sockets ; \
    docker-php-ext-install -j"$(nproc)" xsl ; \
    docker-php-ext-install -j"$(nproc)" zip ; \
    pecl install amqp ; \
    docker-php-ext-enable amqp ; \
    pecl install apcu ; \
    docker-php-ext-enable apcu ; \
    pecl install redis ; \
    docker-php-ext-enable redis ; \
    pecl install --configureoptions 'enable-swoole-curl="yes"' swoole ; \
    docker-php-ext-enable swoole ; \
    pecl install memcached ; \
    docker-php-ext-enable memcached ;

RUN set -ex ; \
    cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini ; \
    sed -E -i -e 's/;?\s*memory_limit\s*=(.+)?$/memory_limit = 2G/g' /usr/local/etc/php/php.ini ; \
    sed -E -i -e 's/;?\s*max_execution_time\s*=(.+)?$/max_execution_time = 300/g' /usr/local/etc/php/php.ini ; \
    sed -E -i -e 's/;?\s*post_max_size\s*=(.+)?$/post_max_size = 30M/g' /usr/local/etc/php/php.ini ; \
    sed -E -i -e 's/;?\s*upload_max_filesize\s*=(.+)?$/upload_max_filesize = 30M/g' /usr/local/etc/php/php.ini ; \
    \
    sed -E -i -e 's/^user = www-data/user = root/g' /usr/local/etc/php-fpm.d/www.conf ; \
    sed -E -i -e 's/^group = www-data/group = root/g' /usr/local/etc/php-fpm.d/www.conf ; \
    sed -E -i -e 's/^pm.max_children = 5/pm.max_children = 86/g' /usr/local/etc/php-fpm.d/www.conf ; \
    sed -E -i -e 's/^pm.start_servers = 2/pm.start_servers = 21/g' /usr/local/etc/php-fpm.d/www.conf ; \
    sed -E -i -e 's/^pm.min_spare_servers = 1/pm.min_spare_servers = 21/g' /usr/local/etc/php-fpm.d/www.conf ; \
    sed -E -i -e 's/^pm.max_spare_servers = 3/pm.max_spare_servers = 64/g' /usr/local/etc/php-fpm.d/www.conf ; \
    sed -E -i -e 's/^;pm.max_requests = 500/pm.max_requests = 100/g' /usr/local/etc/php-fpm.d/www.conf ; \
    sed -E -i -e 's/^;pm.process_idle_timeout = 10s;/pm.process_idle_timeout = 60s/g' /usr/local/etc/php-fpm.d/www.conf ; \
    sed -E -i -e 's/^;pm.status_listen = 127.0.0.1:9001/pm.status_listen = 127.0.0.1:9001/g' /usr/local/etc/php-fpm.d/www.conf ; \
    sed -E -i -e 's/^;emergency_restart_interval = 0/emergency_restart_interval = 3/g' /usr/local/etc/php-fpm.conf.default ; \
    sed -E -i -e 's/^;emergency_restart_threshold = 0/emergency_restart_threshold = 1m/g' /usr/local/etc/php-fpm.conf.default ; \
    sed -E -i -e 's/^;process_control_timeout = 0/process_control_timeout = 5s/g' /usr/local/etc/php-fpm.conf.default ; \
    sed -E -i -e 's/^;log_level = notice/log_level = debug/g' /usr/local/etc/php-fpm.conf.default ; \
    sed -E -i -e 's/^;error_log = log\/php-fpm.log/error_log = \/proc\/self\/fd\/2/g' /usr/local/etc/php-fpm.conf


FROM base AS php-prod

RUN set -ex ; \
    sed -E -i -e 's/;?\s*opcache.enable\s*=(.+)?$/opcache.enable = 1/g' /usr/local/etc/php/php.ini ; \
    sed -E -i -e 's/;?\s*opcache.enable_cli\s*=(.+)?$/opcache.enable_cli = 1/g' /usr/local/etc/php/php.ini ; \
    sed -E -i -e 's/;?\s*opcache.memory_consumption\s*=(.+)?$/opcache.memory_consumption = 256/g' /usr/local/etc/php/php.ini ; \
    sed -E -i -e 's/;?\s*opcache.max_accelerated_files\s*=(.+)?$/opcache.max_accelerated_files = 20000/g' /usr/local/etc/php/php.ini ; \
    sed -E -i -e 's/;?\s*opcache.validate_timestamps\s*=(.+)?$/opcache.validate_timestamps = 0/g' /usr/local/etc/php/php.ini ; \
    sed -E -i -e 's/;?\s*realpath_cache_size\s*=(.+)?$/realpath_cache_size = 4096K/g' /usr/local/etc/php/php.ini ; \
    sed -E -i -e 's/;?\s*realpath_cache_ttl\s*=(.+)?$/realpath_cache_ttl = 600/g' /usr/local/etc/php/php.ini


FROM base AS php-dev


# hadolint ignore=DL3006
FROM php-${BUILD_TARGET} AS nginx

RUN apk add nginx

COPY ./config/nginx/default.conf /etc/nginx/http.d/default.conf


FROM nginx AS app

WORKDIR /var/www/html

COPY ./entrypoint.sh /entrypoint.sh
COPY ./composer.* ./symfony.* ./

RUN composer install --no-scripts --optimize-autoloader

COPY . .

RUN composer dump-autoload --optimize && \
    composer check-platform-reqs

EXPOSE 80

ENTRYPOINT [ "sh", "/entrypoint.sh" ]
