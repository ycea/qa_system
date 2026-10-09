# dev.ps1 — запуск backend (Laravel) и frontend (Vite) в отдельных окнах

$rootPath = Split-Path -Parent $MyInvocation.MyCommand.Definition
$backendPath = Join-Path $rootPath "backend"
$frontendPath = Join-Path $rootPath "frontend"

Write-Host "==> Запуск Laravel backend..." -ForegroundColor Cyan
Start-Process powershell -ArgumentList @(
    "-NoExit",
    "-Command",
    "cd '$backendPath'; php artisan serve --host=0.0.0.0 --port=8000"
)

Start-Sleep -Seconds 2

Write-Host "==> Запуск Vite frontend..." -ForegroundColor Cyan
Start-Process powershell -ArgumentList @(
    "-NoExit",
    "-Command",
    "cd '$frontendPath'; npm run dev"
)

Write-Host ""
Write-Host "==> Готово!" -ForegroundColor Green
Write-Host "Backend:  http://localhost:8000" -ForegroundColor Yellow
Write-Host "Frontend: http://localhost:3000" -ForegroundColor Yellow
Write-Host ""
Write-Host "Оба процесса запущены в отдельных окнах PowerShell." -ForegroundColor Gray
Write-Host "Чтобы остановить — закрой окна или нажми Ctrl+C в каждом." -ForegroundColor Gray