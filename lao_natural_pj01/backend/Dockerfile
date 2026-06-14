FROM php:8.2-cli

RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /var/www/html
COPY . .

EXPOSE 80

CMD ["sh", "-c", "php migrate.php && php -S 0.0.0.0:80 -t /var/www/html"]
