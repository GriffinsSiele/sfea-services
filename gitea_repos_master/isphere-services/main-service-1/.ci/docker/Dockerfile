FROM php:7.2.24-fpm

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html/2.00

RUN apt update
RUN apt install -y git \
    libpng-dev \
    librabbitmq-dev \
    libxslt1-dev \
    libzip-dev \
    wkhtmltopdf \
    unzip \
    xvfb \
    zip \
    nginx-full \
    nodejs \
    npm

RUN docker-php-ext-install -j"$(nproc)" gd \
    && docker-php-ext-install -j"$(nproc)" intl \
    && docker-php-ext-install -j"$(nproc)" mysqli \
    && docker-php-ext-install -j"$(nproc)" opcache \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql \
    && docker-php-ext-install -j"$(nproc)" sockets \
    && docker-php-ext-install -j"$(nproc)" xsl \
    && docker-php-ext-install -j"$(nproc)" zip

RUN pecl update-channels \
    && pecl install amqp-1.11.0 \
    && docker-php-ext-enable amqp \
    && pecl install redis-5.3.4 \
    && docker-php-ext-enable redis

ENV PHP_INI_MEMORY_LIMIT="128M"
ENV PHP_INI_MAX_EXECUTION_TIME="300"
ENV PHP_INI_POST_MAX_SIZE="30M"
ENV PHP_INI_UPLOAD_MAX_FILESIZE="30M"
ENV PHP_INI_OPCACHE_ENABLE="0"
ENV PHP_INI_OPCACHE_MEMORY_CONSUMPTION="256"
ENV PHP_INI_OPCACHE_MAX_ACCELERATED_FILES="20000"
ENV PHP_INI_OPCACHE_VALIDATE_TIMESTAMPS="0"
ENV PHP_INI_REALPATH_CACHE_SIZE="4096K"
ENV PHP_INI_REALPATH_CACHE_TTL="600"
ENV PHP_INI_DISPLAY_ERRORS="On"

#ENV PHP_FPM_PM_MAX_CHILDREN="386"
ENV PHP_FPM_PM_MAX_CHILDREN="39"
#ENV PHP_FPM_PM_START_SERVERS="128"
ENV PHP_FPM_PM_START_SERVERS="13"
#ENV PHP_FPM_PM_MIN_SPARE_SERVERS="128"
ENV PHP_FPM_PM_MIN_SPARE_SERVERS="13"
#ENV PHP_FPM_PM_MAX_SPARE_SERVERS="256"
ENV PHP_FPM_PM_MAX_SPARE_SERVERS="26"
ENV PHP_FPM_PM_MAX_REQUESTS="10"
ENV PHP_FPM_PM_PROCESS_IDLE_TIMEOUT="30s"
ENV PHP_FPM_PM_STATUS_PATH="\/fpm-status"
ENV PHP_FPM_PING_PATH="\/fpm-ping"
ENV PHP_FPM_EMERGENCY_RESTART_INTERVAL="3"
ENV PHP_FPM_EMERGENCY_RESTART_THRESHOLD="1"
ENV PHP_FPM_PROCESS_CONTROL_TIMEOUT="5s"
ENV PHP_FPM_LOG_LEVEL="warning"
ENV PHP_FPM_ERROR_LOG="\/proc\/self\/fd\/2"

RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini \
    && sed -E -i -e "s/;?\s*memory_limit\s*=(.+)?$/memory_limit = ${PHP_INI_MEMORY_LIMIT}/g" \
    /usr/local/etc/php/php.ini \
    && sed -E -i -e "s/;?\s*max_execution_time\s*=(.+)?$/max_execution_time = ${PHP_INI_MAX_EXECUTION_TIME}/g" \
    /usr/local/etc/php/php.ini \
    && sed -E -i -e "s/;?\s*post_max_size\s*=(.+)?$/post_max_size = ${PHP_INI_POST_MAX_SIZE}/g" \
    /usr/local/etc/php/php.ini \
    && sed -E -i -e "s/;?\s*upload_max_filesize\s*=(.+)?$/upload_max_filesize = ${PHP_INI_UPLOAD_MAX_FILESIZE}/g" \
    /usr/local/etc/php/php.ini \
    && sed -E -i -e "s/;?\s*opcache.enable\s*=(.+)?$/opcache.enable = ${PHP_INI_OPCACHE_ENABLE}/g" \
    /usr/local/etc/php/php.ini \
    && sed -E -i -e "s/;?\s*opcache.memory_consumption\s*=(.+)?$/opcache.memory_consumption = ${PHP_INI_OPCACHE_MEMORY_CONSUMPTION}/g" \
    /usr/local/etc/php/php.ini \
    && sed -E -i -e "s/;?\s*opcache.max_accelerated_files\s*=(.+)?$/opcache.max_accelerated_files = ${PHP_INI_OPCACHE_MAX_ACCELERATED_FILES}/g" \
    /usr/local/etc/php/php.ini \
    && sed -E -i -e "s/;?\s*opcache.validate_timestamps\s*=(.+)?$/opcache.validate_timestamps = ${PHP_INI_OPCACHE_VALIDATE_TIMESTAMPS}/g" \
    /usr/local/etc/php/php.ini \
    && sed -E -i -e "s/;?\s*realpath_cache_size\s*=(.+)?$/realpath_cache_size = ${PHP_INI_REALPATH_CACHE_SIZE}/g" \
    /usr/local/etc/php/php.ini \
    && sed -E -i -e "s/;?\s*realpath_cache_ttl\s*=(.+)?$/realpath_cache_ttl = ${PHP_INI_REALPATH_CACHE_TTL}/g" \
    /usr/local/etc/php/php.ini \
    && sed -E -i -e "s/;?\s*display_errors\s*=(.+)?$/display_errors = ${PHP_INI_DISPLAY_ERRORS}/g" \
    /usr/local/etc/php/php.ini

RUN sed -E -i -e "s/^pm.max_children = 5/pm.max_children = ${PHP_FPM_PM_MAX_CHILDREN}/g" \
    /usr/local/etc/php-fpm.d/www.conf \
    && sed -E -i -e "s/^pm.start_servers = 2/pm.start_servers = ${PHP_FPM_PM_START_SERVERS}/g" \
    /usr/local/etc/php-fpm.d/www.conf \
    && sed -E -i -e "s/^pm.min_spare_servers = 1/pm.min_spare_servers = ${PHP_FPM_PM_MIN_SPARE_SERVERS}/g" \
    /usr/local/etc/php-fpm.d/www.conf \
    && sed -E -i -e "s/^pm.max_spare_servers = 3/pm.max_spare_servers = ${PHP_FPM_PM_MAX_SPARE_SERVERS}/g" \
    /usr/local/etc/php-fpm.d/www.conf \
    && sed -E -i -e "s/^;pm.max_requests = 500/pm.max_requests = ${PHP_FPM_PM_MAX_REQUESTS}/g" \
    /usr/local/etc/php-fpm.d/www.conf \
    && sed -E -i -e "s/^;pm.process_idle_timeout = 10s;/pm.process_idle_timeout = ${PHP_FPM_PM_PROCESS_IDLE_TIMEOUT}/g" \
    /usr/local/etc/php-fpm.d/www.conf \
    && sed -E -i -e "s/^;pm.status_path = \/status/pm.status_path = ${PHP_FPM_PM_STATUS_PATH}/g" \
    /usr/local/etc/php-fpm.d/www.conf \
    && sed -E -i -e "s/^;ping.path = \/ping/ping.path = ${PHP_FPM_PING_PATH}/g" \
    /usr/local/etc/php-fpm.d/www.conf \
    && sed -E -i -e "s/^;emergency_restart_interval = 0/emergency_restart_interval = ${PHP_FPM_EMERGENCY_RESTART_INTERVAL}/g" \
    /usr/local/etc/php-fpm.conf \
    && sed -E -i -e "s/^;emergency_restart_threshold = 0/emergency_restart_threshold = ${PHP_FPM_EMERGENCY_RESTART_THRESHOLD}/g" \
    /usr/local/etc/php-fpm.conf \
    && sed -E -i -e "s/^;process_control_timeout = 0/process_control_timeout = ${PHP_FPM_PROCESS_CONTROL_TIMEOUT}/g" \
    /usr/local/etc/php-fpm.conf \
    && sed -E -i -e "s/^;log_level = notice/log_level = ${PHP_FPM_LOG_LEVEL}/g" \
    /usr/local/etc/php-fpm.conf \
    && sed -E -i -e "s/^;error_log = log\/php-fpm.log/error_log = ${PHP_FPM_ERROR_LOG}/g" \
    /usr/local/etc/php-fpm.conf

ENV XDG_RUNTIME_DIR=/run/user/33
RUN mkdir -p "${XDG_RUNTIME_DIR}" && \
    chown -R 33:33 "${XDG_RUNTIME_DIR}" && \
    chmod 0700 "${XDG_RUNTIME_DIR}" && \
    fc-cache -f -v

EXPOSE 80
EXPOSE 9000

ENV COMPOSER_ALLOW_SUPERUSER=1

COPY .ci/docker/entrypoint.sh /entrypoint.sh

COPY bulkAuto/composer.json bulkAuto/composer.lock ./bulkAuto/
RUN cd bulkAuto && composer install

COPY bulkAuto/package.json bulkAuto/package-lock.json bulkAuto/
RUN cd bulkAuto && npm install

COPY composer.json composer.lock ./
RUN composer install

COPY .ci/docker/nginx/snippets/. /etc/nginx/snippets/.
COPY .ci/docker/nginx/sites-enabled/. /etc/nginx/sites-enabled/.

COPY . .

ENTRYPOINT [ "bash", "/entrypoint.sh" ]
