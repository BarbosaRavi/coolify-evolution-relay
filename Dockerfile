# syntax=docker/dockerfile:1

# ---------- estágio 1: dependências ----------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
      --no-dev \
      --no-scripts \
      --no-autoloader \
      --prefer-dist \
      --no-interaction

COPY . .
RUN composer dump-autoload --optimize --no-dev

# ---------- estágio 2: imagem final ----------
FROM dunglas/frankenphp:php8.4

RUN install-php-extensions opcache

ENV SERVER_NAME=:80

WORKDIR /app

COPY --from=vendor /app /app

RUN chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
