FROM ghcr.io/ghostwriter/php:7.2

COPY --from=ghcr.io/devgine/composer-php:v2-php7.2-alpine /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html/daemon

RUN set -ex ; \
    apt update ; \
    apt install -y \
    libpng-dev \
    librabbitmq-dev \
    libxslt1-dev \
    libzip-dev ; \
    docker-php-ext-install -j"$(nproc)" gd ; \
    docker-php-ext-install -j"$(nproc)" mysqli ; \
    docker-php-ext-install -j"$(nproc)" opcache ; \
    docker-php-ext-install -j"$(nproc)" xsl ; \
    docker-php-ext-install -j"$(nproc)" zip ; \
    pecl install amqp && \
    docker-php-ext-enable amqp ; \
    pecl install apcu && \
    docker-php-ext-enable apcu ; \
    pecl install redis && \
    docker-php-ext-enable redis ; \
    rm -rf /tmp/pear

RUN ln -sf /usr/local/bin/php /usr/bin/php

ENV PHP_INI_MEMORY_LIMIT="-1"
ENV PHP_INI_MAX_EXECUTION_TIME="300"
ENV PHP_INI_POST_MAX_SIZE="30M"
ENV PHP_INI_UPLOAD_MAX_FILESIZE="30M"
ENV PHP_INI_OPCACHE_ENABLE_CLI="1"
ENV PHP_INI_OPCACHE_MEMORY_CONSUMPTION="256"
ENV PHP_INI_OPCACHE_MAX_ACCELERATED_FILES="20000"
ENV PHP_INI_OPCACHE_VALIDATE_TIMESTAMPS="0"
ENV PHP_INI_REALPATH_CACHE_SIZE="4096K"
ENV PHP_INI_REALPATH_CACHE_TTL="600"

RUN set -ex ; \
    sed -E -i -e "s/;?\s*memory_limit\s*=(.+)?$/memory_limit = ${PHP_INI_MEMORY_LIMIT}/g" /usr/local/etc/php/php.ini ; \
    sed -E -i -e "s/;?\s*max_execution_time\s*=(.+)?$/max_execution_time = ${PHP_INI_MAX_EXECUTION_TIME}/g" /usr/local/etc/php/php.ini ; \
    sed -E -i -e "s/;?\s*post_max_size\s*=(.+)?$/post_max_size = ${PHP_INI_POST_MAX_SIZE}/g" /usr/local/etc/php/php.ini ; \
    sed -E -i -e "s/;?\s*upload_max_filesize\s*=(.+)?$/upload_max_filesize = ${PHP_INI_UPLOAD_MAX_FILESIZE}/g" /usr/local/etc/php/php.ini ; \
    sed -E -i -e "s/;?\s*opcache.enable_cli\s*=(.+)?$/opcache.enable_cli = ${PHP_INI_OPCACHE_ENABLE_CLI}/g" /usr/local/etc/php/php.ini ; \
    sed -E -i -e "s/;?\s*opcache.memory_consumption\s*=(.+)?$/opcache.memory_consumption = ${PHP_INI_OPCACHE_MEMORY_CONSUMPTION}/g" /usr/local/etc/php/php.ini ; \
    sed -E -i -e "s/;?\s*opcache.max_accelerated_files\s*=(.+)?$/opcache.max_accelerated_files = ${PHP_INI_OPCACHE_MAX_ACCELERATED_FILES}/g" /usr/local/etc/php/php.ini ; \
    sed -E -i -e "s/;?\s*opcache.validate_timestamps\s*=(.+)?$/opcache.validate_timestamps = ${PHP_INI_OPCACHE_VALIDATE_TIMESTAMPS}/g" /usr/local/etc/php/php.ini ; \
    sed -E -i -e "s/;?\s*realpath_cache_size\s*=(.+)?$/realpath_cache_size = ${PHP_INI_REALPATH_CACHE_SIZE}/g" /usr/local/etc/php/php.ini ; \
    sed -E -i -e "s/;?\s*realpath_cache_ttl\s*=(.+)?$/realpath_cache_ttl = ${PHP_INI_REALPATH_CACHE_TTL}/g" /usr/local/etc/php/php.ini ;

COPY public/composer.json public/composer.lock ./
RUN composer install

COPY public/. ./
