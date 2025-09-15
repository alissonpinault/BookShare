FROM php:8.2-apache

# Installer PDO MySQL et MongoDB
RUN apt-get update && apt-get install -y libonig-dev libzip-dev unzip \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && docker-php-ext-install pdo_mysql
