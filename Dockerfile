FROM php:8.0-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html/storage

WORKDIR /var/www/html
