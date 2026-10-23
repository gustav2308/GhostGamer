
FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo_mysql \
    && a2enmod rewrite

WORKDIR /var/www/html

COPY . /var/www/html/

COPY uploads.ini /usr/local/etc/php/conf.d/uploads.ini

RUN chown -R www-data:www-data /var/www/html

RUN php -i | grep -E "upload_max_filesize|post_max_size|memory_limit"

EXPOSE 80
