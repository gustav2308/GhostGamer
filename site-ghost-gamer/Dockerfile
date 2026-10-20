
FROM php:8.2-apache

RUN echo "=== MPM habilitados ===" \
    && ls -la /etc/apache2/mods-enabled/mpm* \
    && echo "=== Configuração dos MPM ===" \
    && grep -R "LoadModule mpm_" /etc/apache2/

RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN a2enmod rewrite

WORKDIR /var/www/html

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
