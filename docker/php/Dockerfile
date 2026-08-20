FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    libmemcached-dev zip unzip git \
    && docker-php-ext-install pdo pdo_mysql \
    && pecl install memcached \
    && docker-php-ext-enable memcached

RUN a2enmod rewrite

COPY apache.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html