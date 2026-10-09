# setup.ps1
Write-Host "==> Поднимаем контейнеры..." -ForegroundColor Cyan
docker compose up -d --build

Write-Host "==> Ждём готовности PostgreSQL..." -ForegroundColor Cyan
Start-Sleep -Seconds 8

if (-not (Test-Path backend/.env)) {
    Write-Host "==> Копируем .env..." -ForegroundColor Cyan
    Copy-Item backend/.env.example backend/.env
}

Write-Host "==> Генерируем APP_KEY..." -ForegroundColor Cyan
docker compose exec -T backend php artisan key:generate

Write-Host "==> Миграции и сиды..." -ForegroundColor Cyan
docker compose exec -T backend php artisan migrate --seed --force

Write-Host "==> Готово: http://localhost:8000 / http://localhost:3000" -ForegroundColor Green