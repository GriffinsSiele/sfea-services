FROM php:8.2-fpm
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html/dashbord
RUN apt update && \
    apt install -y bash git libcurl4 libcurl4-openssl-dev libxml2-dev zip && \
    apt install -y zlib1g zlib1g-dev libpng-dev && \
    apt install -y libzip-dev unzip && \
    docker-php-ext-install curl exif dom intl mysqli pdo_mysql simplexml iconv gd zip
ARG UID=1000
ARG GID=1000
RUN groupadd -g ${GID} www && \
    useradd -m -g ${UID} -g ${GID} -s /bin/bash www && \
    chown -R www:www /var/www/html/dashbord
USER ${UID}:${GID}
COPY --chown=${UID}:${GID} \
     src/composer.json \
     src/composer.lock \
     /var/www/html/dashbord/
RUN composer install --no-scripts
COPY --chown=${UID}:${GID} \
    ./src \
    /var/www/html/dashbord
RUN composer install --optimize-autoloader
