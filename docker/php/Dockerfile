FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    bash \
    git \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    zip \
  && docker-php-ext-install \
    bcmath \
    intl \
    mbstring \
    pdo_mysql \
    pcntl \
    zip \
  && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
  && pecl install redis \
  && docker-php-ext-enable redis \
  && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/backend
