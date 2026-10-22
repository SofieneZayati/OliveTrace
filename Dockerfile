# syntax=docker/dockerfile:1
FROM node:24-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY vite.config.js ./
RUN npm run build

FROM php:8.4-apache-bookworm AS app
RUN apt-get update && apt-get install -y --no-install-recommends \
        libicu-dev libonig-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libzip-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring intl gd zip opcache bcmath \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader
COPY . .
COPY --from=assets /app/public/build ./public/build
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/olivetrace.ini
COPY docker/start.sh /usr/local/bin/olivetrace-start
RUN printf 'Listen ${PORT}\n' > /etc/apache2/ports.conf \
    && chmod +x /usr/local/bin/olivetrace-start \
    && composer dump-autoload --no-dev --optimize \
    && composer check-platform-reqs --no-dev
ENV PORT=8080 APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr
EXPOSE 8080
ENTRYPOINT ["olivetrace-start"]
CMD ["apache2-foreground"]
