
FROM php:8.2-apache

RUN a2dismod mpm_event mpm_worker 2>/dev/null || true \
    && a2enmod mpm_prefork \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && a2enmod rewrite \
    && apache2ctl -M

WORKDIR /var/www/html

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
