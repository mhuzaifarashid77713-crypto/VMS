FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    default-mysql-client \
    && docker-php-ext-install pdo pdo_mysql mysqli mbstring

COPY . /var/www/html/

EXPOSE 80

CMD ["apache2-foreground"]
