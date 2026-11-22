FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libzip-dev zip unzip \
    libpq-dev \
    && docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip dom simplexml xml xmlreader xmlwriter

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Копируем только composer-файлы, чтобы кешировать слой
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --prefer-dist --no-dev

# Копируем остальное
COPY . .

RUN composer dump-autoload --optimize
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache || true

EXPOSE 9000

# Копируем entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && \
    chmod +x /usr/local/bin/entrypoint.sh
EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]