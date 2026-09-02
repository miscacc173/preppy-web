FROM php:8.4-fpm-alpine
RUN apk add --no-cache bash git curl libpq-dev icu-dev oniguruma-dev unzip nodejs npm nginx supervisor \
 && docker-php-ext-install pdo_pgsql intl mbstring opcache
WORKDIR /var/www/html
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . .
RUN composer install --no-interaction --prefer-dist --optimize-autoloader \
 && npm install && npm run build \
 && chown -R www-data:www-data storage bootstrap/cache
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
EXPOSE 80
CMD ["/usr/bin/supervisord","-c","/etc/supervisord.conf"]
