FROM php:8.2-cli-alpine

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN apk add $PHPIZE_DEPS \
      libzip-dev \
      postgresql-dev \
      zip && \
    docker-php-ext-install pdo_pgsql && \
    docker-php-ext-install zip

RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini \
    && sed -E -i -e 's/;?\s*memory_limit\s*=.+$/memory_limit = 1G/g' /usr/local/etc/php/php.ini \
    && sed -E -i -e 's/;?\s*opcache.enable\s*=.+$/opcache.enable = 1/g' /usr/local/etc/php/php.ini

WORKDIR /app

COPY . .
