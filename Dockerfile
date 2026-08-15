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

# A imagem base traz apenas pdo_sqlite. Como a aplicação usa Postgres
# (DB_CONNECTION=pgsql), sem pdo_pgsql o artisan migrate falha com
# "could not find driver" e o contêiner morre no boot.
RUN install-php-extensions opcache pdo_pgsql intl

ENV SERVER_NAME=:80

WORKDIR /app

COPY --from=vendor /app /app

RUN chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

HEALTHCHECK --interval=15s --timeout=5s --start-period=60s --retries=3 \
      CMD curl -fsS http://127.0.0.1:80/up || exit 1

ENTRYPOINT ["entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
