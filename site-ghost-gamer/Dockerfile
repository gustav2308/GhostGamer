
FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo_mysql \
    && a2dismod mpm_event mpm_worker \
    && a2enmod mpm_prefork rewrite

WORKDIR /var/www/html

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
