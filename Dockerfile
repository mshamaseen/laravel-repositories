ARG PHP_VERSION=8.3

FROM php:${PHP_VERSION}

RUN apt-get update && \
    apt-get install -y && \
    docker-php-ext-install -j$(nproc) pdo_mysql

# Copy the Composer binary from the specified stage
COPY --from=composer:2.5.4 /usr/bin/composer /usr/local/bin/composer

WORKDIR /code

COPY ./ ./

RUN composer install

CMD ["./vendor/bin/phpunit"]
