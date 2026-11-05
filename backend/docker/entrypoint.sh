#!/bin/sh
set -e

cd /var/www

# 1. Установить зависимости, если vendor отсутствует или composer.lock новее
if [ ! -f vendor/autoload.php ] || [ composer.lock -nt vendor/autoload.php ]; then
    echo "==> Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# 2. Сгенерировать .env и APP_KEY, если их нет
if [ ! -f .env ]; then
    echo "==> Creating .env from .env.example..."
    cp .env.example .env
fi

# Принудительно выставляем PostgreSQL
echo "==> Forcing DB_CONNECTION=pgsql in .env..."
sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=pgsql/' .env

if ! grep -q "^APP_KEY=base64:" .env; then
    echo "==> Generating APP_KEY..."
    php artisan key:generate --force
fi

# Сбрасываем кэш конфигурации
php artisan config:clear || true

# 3. Сгенерировать кэш пакетов
echo "==> Discovering packages..."
php artisan package:discover --ansi || true

# 4. Дождаться БД и выполнить миграции
echo "==> Waiting for database..."
until php -r "try { new PDO('pgsql:host=db;port=5432;dbname=' . getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); echo 'ok'; } catch (Exception \$e) { exit(1); }" > /dev/null 2>&1; do
    sleep 1
done

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Starting PHP-FPM..."
exec "$@"