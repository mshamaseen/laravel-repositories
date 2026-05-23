ARG PHP_VERSION=8.4

FROM php:${PHP_VERSION}

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    && docker-php-ext-install pdo_mysql


# Copy the Composer binary from the specified stage
COPY --from=composer:2.9.8 /usr/bin/composer /usr/local/bin/composer

WORKDIR /code

COPY ./ ./

RUN composer install

RUN git config --global --add safe.directory /code

CMD ["./vendor/bin/phpunit"]