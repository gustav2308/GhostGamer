
FROM php:8.2-apache

RUN a2dismod mpm_event mpm_worker || true
RUN a2enmod mpm_prefork
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN a2enmod rewrite

WORKDIR /var/www/html

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
