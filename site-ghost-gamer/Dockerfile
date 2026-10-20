
FROM php:8.2-apache

RUN ls -l /etc/apache2/mods-enabled/mpm*

RUN docker-php-ext-install mysqli pdo_mysql \
    && a2enmod rewrite \
    && apache2ctl -t

WORKDIR /var/www/html

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
