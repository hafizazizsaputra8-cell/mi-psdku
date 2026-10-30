FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends default-mysql-client unzip git \
    && docker-php-ext-install pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app
COPY . .

RUN composer install --no-interaction --prefer-dist --optimize-autoloader

EXPOSE 3003

CMD ["sh", "-c", "until php artisan migrate --force; do echo 'Menunggu database order-db siap...'; sleep 2; done; php artisan serve --host=0.0.0.0 --port=3003"]
