FROM php:8.1-fpm

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    curl \
    vim \
    libicu-dev

RUN pecl install xdebug && docker-php-ext-enable xdebug

COPY ./docker/xdebug.ini "${PHP_INI_DIR}/conf.d"