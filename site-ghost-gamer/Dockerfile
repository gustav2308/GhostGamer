
FROM php:8.2-apache

RUN find /etc/apache2 -type f \( -name '*.conf' -o -name '*.load' \) \
    -exec sed -i '/^[[:space:]]*LoadModule mpm_/d' {} + \
    && a2enmod mpm_prefork rewrite \
    && docker-php-ext-install mysqli pdo_mysql

WORKDIR /var/www/html

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
