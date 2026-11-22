from playwright.sync_api import Locator, expect

from pages.base_page import BasePage


class BugsPage(BasePage):
    """Страница списка багов проекта (/projects/:id/bugs)."""

    BACK = "← Назад к проекту"
    CREATE = "Создать баг"

    def open(self, project_id: int | str) -> None:
        self.page.goto(f"/projects/{project_id}/bugs")

    @property
    def dialog(self) -> Locator:
        return self.page.get_by_role("dialog")

    # --- Навигация ---

    def expect_back_to_project(self) -> None:
        expect(self.page.get_by_role("button", name=self.BACK, exact=True)).to_be_visible()

    def click_back_to_project(self) -> None:
        self.page.get_by_role("button", name=self.BACK, exact=True).click()

    # --- Список багов ---

    def expect_table_visible(self) -> None:
        expect(self.page.locator("table")).to_be_visible()

    def expect_rows_count(self, count: int) -> None:
        expect(self.page.locator("table tbody tr")).to_have_count(count)

    def expect_bug_in_table(self, title: str) -> None:
        expect(self.page.locator(f'tr:has-text("{title}")')).to_be_visible()

    def expect_bug_absent(self, title: str) -> None:
        expect(self.page.locator(f'tr:has-text("{title}")')).to_have_count(0)

    def click_bug_row(self, title: str) -> None:
        self.page.locator(f'tr:has-text("{title}")').first.click()

    # --- Фильтры и поиск ---

    def search(self, value: str) -> None:
        self.page.get_by_label("Поиск по заголовку").fill(value)

    def filter_by_status(self, name: str) -> None:
        self.page.get_by_role("combobox", name="Статус", exact=True).click()
        self.page.get_by_role("option", name=name).click()

    def reset_filters(self) -> None:
        self.page.get_by_role("button", name="Сбросить всё").click()

    # --- Быстрые действия прямо в таблице ---

    def change_status_in_table(self, title: str, status_name: str) -> None:
        self.page.get_by_label(f"Статус: {title}").click()
        self.page.get_by_role("option", name=status_name).click()

    def assign_in_table(self, title: str, member_name: str) -> None:
        self.page.get_by_label(f"Исполнитель: {title}").click()
        self.page.get_by_role("option", name=member_name).click()

    # --- Форма создания бага ---

    def click_create(self) -> None:
        self.page.get_by_role("button", name=self.CREATE, exact=True).click()

    def fill_bug_form(
        self,
        *,
        title: str,
        description: str,
        steps: str,
        priority: str,
        severity: str,
    ) -> None:
        self.dialog.get_by_label("Заголовок").fill(title)
        self.dialog.get_by_label("Описание").fill(description)
        self.dialog.get_by_label("Шаги воспроизведения").fill(steps)
        self.dialog.get_by_label("Приоритет").click()
        self.page.get_by_role("option", name=priority).click()
        self.dialog.get_by_label("Серьёзность").click()
        self.page.get_by_role("option", name=severity).click()

    def submit_form(self) -> None:
        self.dialog.get_by_role("button", name="Сохранить", exact=True).click()

    def cancel_form(self) -> None:
        self.dialog.get_by_role("button", name="Отмена", exact=True).click()

    def expect_error(self, message: str | None = None) -> None:
        alert = self.page.locator(".MuiAlert-root").first
        expect(alert).to_be_visible()
        if message:
            expect(alert).to_contain_text(message)

    # --- Детали бага ---

    def expect_details(self, title: str) -> None:
        expect(self.page.get_by_role("heading", level=4, name=title)).to_be_visible()

    def expect_back_to_list(self) -> None:
        expect(
            self.page.get_by_role("button", name="← Назад к списку багов", exact=True)
        ).to_be_visible()

    def click_edit(self) -> None:
        self.page.get_by_role("button", name="Редактировать", exact=True).click()

    def expect_history_section(self) -> None:
        expect(self.page.get_by_text("История изменений")).to_be_visible()

    def expect_history_record(self, text: str) -> None:
        expect(self.page.get_by_text(text, exact=False).first).to_be_visible()

    def change_status_on_details(self, status_name: str) -> None:
        self.page.get_by_role("combobox", name="Статус", exact=True).click()
        self.page.get_by_role("option", name=status_name).click()

    def add_comment(self, body: str) -> None:
        self.page.get_by_label("Новый комментарий").fill(body)
        self.page.get_by_role("button", name="Отправить", exact=True).click()

    def expect_comment(self, body: str) -> None:
        expect(self.page.get_by_text(body, exact=False).first).to_be_visible()
