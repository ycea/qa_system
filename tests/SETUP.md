# Настройка окружения для e2e тестов (Playwright + Python)

## 1. Установите Python
Скачайте и установите Python 3.10+ с официального сайта: https://www.python.org/

При установке отметьте галочку "Add Python to PATH".

## 2. Установите зависимости
В корне проекта выполните:

```powershell
pip install -r tests/requirements.txt
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

## 5. Линтер и проверка типов (Ruff + Mypy)
Конфигурация инструментов лежит в корневом `pyproject.toml`, зависимости — в `tests/requirements.txt`.
Тот же набор проверок выполняется в CI (job `tests-lint`):

```powershell
python -m ruff check tests           # линтер
python -m ruff format --check tests  # форматирование
python -m mypy tests                 # проверка типов
```

Автоформатирование и автоисправления:

```powershell
python -m ruff format tests
python -m ruff check tests --fix
```

## 6. Playwright: строки, регулярки и glob (частые грабли)
Проверено на этом окружении (Playwright + Python 3.14):

| Что нужно | Как писать | Итог |
| --- | --- | --- |
| URL совпадает точно | `expect(page).to_have_url("/projects/5")` | ✅ относительный путь разрешается от `base_url` |
| URL содержит фрагмент | `expect_url_to_contain("/projects/")` (внутри — `re.compile(re.escape(...))`) | ✅ |
| URL соответствует регулярке | `expect_url_matches(r"/projects/\d+")` | ✅ |
| glob в `to_have_url` | `expect(page).to_have_url("**/projects/*")` | ❌ не поддерживается |
| glob в `wait_for_url` | `page.wait_for_url("**/projects/*")` | ✅ |
| текст в `:has-text()` | `page.locator('tr:has-text("Новая")')` — кавычки обязательны | ✅ |
| regex в `:has-text()` | `page.locator(r'tr:has-text(/\d+/)')` — JS-синтаксис `/.../` | ✅ |

Правила:
- **`r`-строка нужна только для regex-паттернов** с `\d`, `\b`, `\w`, `\.`: без неё `'\b'` превращается в backspace и регулярка молча ничего не находит, а `'\d'` даёт `SyntaxWarning` (Python 3.12+, ловится ruff-правилом `W605`).
- Строка **никогда** не становится регуляркой автоматически — нужен `re.compile(...)` (raw-строка сама по себе не помогает).
- `get_by_text`, `to_have_text`, `to_contain_text`, `to_have_url` принимают `str | re.Pattern`; для `to_have_url` строка — это строгое сравнение URL, а не шаблон.