export default {
    extends: ["@commitlint/config-conventional"],
    rules: {
        "type-enum": [
            2,
            "always",
            [
                "feat", // новая функциональность
                "fix", // исправление бага
                "docs", // документация
                "style", // форматирование (без изменения логики)
                "refactor", // рефакторинг
                "perf", // оптимизация
                "test", // тесты
                "build", // сборка, зависимости
                "ci", // CI/CD
                "chore", // рутина
                "revert", // откат
            ],
        ],
        "scope-enum": [
            2,
            "always",
            [
                "auth", // AUTH-*
                "projects", // PROJ-*
                "bugs", // BUG-*
                "metrics", // METR-*
                "dashboard", // DASH-*
                "reports", // REPT-*
                "infra", // INF-* (Docker, CI, настройки)
                "deps", // зависимости
                "ci", // GitHub Actions
                "docs", // документация
                "config", // конфиги
                "frontend", // общие правки фронта
                "backend", // общие правки бэка
            ],
        ],
        "scope-empty": [1, "never"], // предупреждение, если нет scope
        "subject-case": [0], // отключаем проверку регистра (русский)
        "subject-empty": [2, "never"], // subject обязателен
        "subject-full-stop": [2, "never", "."], // без точки в конце
        "header-max-length": [2, "always", 100], // макс длина заголовка
        "body-leading-blank": [2, "always"], // пустая строка перед body
        "footer-leading-blank": [2, "always"],
    },
};
