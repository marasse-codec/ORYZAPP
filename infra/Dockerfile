FROM php:8.4-fpm-alpine

# Install extension installer tool
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions

# Installation des utilitaires système de base
RUN apk add --no-cache \
    bash \
    curl \
    zip \
    unzip

# Installation propre de toutes les extensions PHP (inclut zip et pcntl)
RUN install-php-extensions \
    pcov \
    pdo_mysql \
    bcmath \
    gd \
    zip \
    pcntl \
    intl


# Récupération de Composer directement depuis l'image officielle
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www