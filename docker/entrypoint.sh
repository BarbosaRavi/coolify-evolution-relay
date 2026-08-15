#!/bin/sh
set -e

mkdir -p storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs
chown -R www-data:www-data storage bootstrap/cache

# O Postgres do Coolify pode subir depois desta aplicação. Sem esta espera o
# migrate falha, o entrypoint aborta (set -e) e o contêiner entra em loop de
# restart até o Coolify desistir.
wait_for_db() {
    attempt=1
    while [ "$attempt" -le 60 ]; do
        if php -r '
            $dsn = sprintf(
                "pgsql:host=%s;port=%s;dbname=%s",
                getenv("DB_HOST") ?: "127.0.0.1",
                getenv("DB_PORT") ?: "5432",
                getenv("DB_DATABASE") ?: "postgres"
            );
            try {
                new PDO($dsn, getenv("DB_USERNAME") ?: null, getenv("DB_PASSWORD") ?: null);
                exit(0);
            } catch (Throwable $e) {
                fwrite(STDERR, $e->getMessage() . PHP_EOL);
                exit(1);
            }
        '; then
            echo "Banco de dados disponível (tentativa ${attempt})."
            return 0
        fi

        echo "Aguardando o banco de dados... (tentativa ${attempt}/60)"
        attempt=$((attempt + 1))
        sleep 2
    done

    echo "Banco de dados indisponível após 120s. Abortando." >&2
    return 1
}

wait_for_db

php artisan config:cache
php artisan route:cache
php artisan migrate --force
php artisan permission:sync

exec "$@"
