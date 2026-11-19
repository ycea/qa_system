# Настройка окружения для e2e тестов (Playwright + Python)

## 1. Установите Python
Скачайте и установите Python 3.10+ с официального сайта: https://www.python.org/

При установке отметьте галочку "Add Python to PATH".

## 2. Установите зависимости
В корне проекта выполните:

```powershell
pip install pytest playwright pytest-playwright python-dotenv
playwright install chromium
playwright install-deps chromium
```

## 3. Переменные окружения
Создайте файл `.env` в папке `tests/`:

```ini
BASE_URL=http://localhost:3000
API_URL=http://localhost:8000/api
```

## 4. Запуск тестов
Убедитесь, что frontend и backend запущены (например, через `docker-compose up` или локально).

Запуск всех тестов:
```powershell
pytest tests/ -v
```

Запуск только конкретного файла:
```powershell
pytest tests/test_auth.py -v
```

Запуск с отчётом HTML:
```powershell
pytest tests/ --html=report.html --self-contained-html
```