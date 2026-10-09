FROM php:8.3-cli-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev libonig-dev libxml2-dev libzip-dev unzip git && docker-php-ext-install pdo_pgsql mbstring xml zip && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY . .
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader && mkdir -p storage/app/private storage/framework/cache storage/framework/sessions storage/framework/views storage/logs && chmod -R 775 storage bootstrap/cache
ENV APP_ENV=production APP_DEBUG=false PORT=8001
EXPOSE 8001
CMD ["sh", "-c", "php artisan migrate --force && php -S 0.0.0.0:${PORT:-8001} -t public router.php"]
