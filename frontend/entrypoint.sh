#!/bin/sh
set -e

echo "==> [frontend] Обновляем Vite до патченной версии..."
npm install vite@^8.3.3 --save-dev

echo "==> [frontend] Безопасный npm audit fix (оставшиеся moderate требуют --force — пропускаем)..."
npm audit fix || true

echo "==> [frontend] Запускаем dev-сервер..."
exec "$@"