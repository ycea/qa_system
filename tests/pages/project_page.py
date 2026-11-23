from playwright.sync_api import Locator, Page, expect

from pages.base_page import BasePage


class ProjectPage(BasePage):
    """Страница проекта: обзор, команда и справочники.

    Селекторы ориентированы на реальный DOM MUI (роли, label и data-testid иконок),
    а не на классы и несуществующие атрибуты вида `button[color="error"]`.
    """

    MEMBERS_TABLE = "table"
    ERROR_ALERT = ".MuiAlert-root"
    DICTIONARIES_HEADING = "text=Справочники проекта"
    DELETE_ICON_BUTTON = 'button[data-testid="delete-member-btn"]'
    EDIT_ICON_BUTTON = 'button[data-testid="edit-member-btn"]'

    TABS = {
        "overview": "Обзор",
        "team": "Команда",
        "dictionaries": "Справочники",
    }
    DICTIONARY_TYPE_TABS = {
        "statuses": "Статусы",
        "priorities": "Приоритеты",
        "severities": "Серьёзности",
    }
    ROLE_LABELS = {
        "admin": "Администратор",
        "developer": "Разработчик",
        "tester": "Тестировщик",
    }

    def __init__(self, page: Page) -> None:
        super().__init__(page)
        self.current_dictionary_type = "statuses"

    # --- Навигация и вкладки ---

    def open_project(self, project_id: int | str) -> None:
        self.page.goto(f"/projects/{project_id}")

    def expect_project_opened(self, project_name: str) -> None:
        expect(self.page.get_by_role("heading", name=project_name, level=4)).to_be_visible()

    def click_tab(self, tab: str) -> None:
        self.page.get_by_role("tab", name=self.TABS[tab]).click()

    def click_overview_tab(self) -> None:
        self.click_tab("overview")

    def click_team_tab(self) -> None:
        self.click_tab("team")

    def click_dictionaries_tab(self) -> None:
        self.click_tab("dictionaries")

    def expect_active_tab(self, tab: str) -> None:
        expect(self.page.get_by_role("tab", name=self.TABS[tab])).to_have_attribute(
            "aria-selected", "true"
        )

    def expect_team_table_visible(self) -> None:
        expect(self.page.locator(self.MEMBERS_TABLE)).to_be_visible()

    def expect_member_with_role(self, login: str, role: str) -> None:
        row = self._member_row(login)
        expect(row).to_be_visible()
        expect(row).to_contain_text(role)

    def expect_member_absent(self, login: str) -> None:
        expect(self._member_row(login)).to_have_count(0)

    def expect_no_management_controls(self) -> None:
        # явно фиксируем, что кнопок удаления нет ни у одного участника
        expect(self.page.locator(self.DELETE_ICON_BUTTON)).to_have_count(0)

    def expect_delete_button_visible(self) -> None:
        expect(self.page.locator(self.DELETE_ICON_BUTTON).first).to_be_visible()

    def expect_member_delete_button_disabled(self, login: str) -> None:
        expect(self._member_row(login).locator(self.DELETE_ICON_BUTTON)).to_be_disabled()

    def click_add_member(self) -> None:
        self.page.get_by_role("button", name="Добавить участника", exact=True).click()

    def fill_member_login(self, value: str) -> None:
        self.dialog.get_by_label("Логин пользователя").fill(value)

    def select_member_role(self, role: str) -> None:
        # MUI Select — это combobox с меню, а не нативный <select>
        self.dialog.get_by_role("combobox").click()
        self.page.get_by_role("option", name=self.ROLE_LABELS[role]).click()

    def expect_member_role_selected(self, role: str) -> None:
        """Выбранное значение в селекте роли: MUI Select показывает текст выбранного пункта."""
        expect(self.dialog.get_by_role("combobox")).to_contain_text(self.ROLE_LABELS[role])

    def submit_add_member(self) -> None:
        self.dialog.get_by_role("button", name="Добавить", exact=True).click()

    def expect_error(self, message: str | None = None) -> None:
        alert = self.page.locator(self.ERROR_ALERT)
        expect(alert).to_be_visible()
        if message:
            expect(alert).to_contain_text(message)

    def click_delete_member(self, login: str) -> None:
        self._member_row(login).locator(self.DELETE_ICON_BUTTON).click()

    def confirm_delete(self, project_name: str) -> None:
        self.dialog.get_by_label(f'Введите "{project_name}"').fill(project_name)
        self.dialog.get_by_role("button", name="Удалить проект", exact=True).click()

    # --- Справочники ---

    def expect_dictionaries_tab_hidden(self) -> None:
        # вкладка не рендерится вовсе, поэтому проверяем полное отсутствие элемента
        expect(self.page.get_by_role("tab", name=self.TABS["dictionaries"])).to_have_count(0)

    def expect_dictionaries_tab_visible(self) -> None:
        expect(self.page.get_by_role("tab", name=self.TABS["dictionaries"])).to_be_visible()

    def expect_dictionaries_view_opened(self) -> None:
        expect(self.page.locator(self.DICTIONARIES_HEADING)).to_be_visible()

    def click_dictionary_type(self, dict_type: str) -> None:
        self.current_dictionary_type = dict_type
        self.page.get_by_role("tab", name=self.DICTIONARY_TYPE_TABS[dict_type]).click()

    def click_add_dictionary(self) -> None:
        label = self.DICTIONARY_TYPE_TABS[self.current_dictionary_type]
        self.page.get_by_role("button", name=f"Добавить в «{label}»", exact=True).click()

    def fill_dictionary_name(self, value: str) -> None:
        self.dialog.get_by_label("Название").fill(value)

    def submit_dictionary(self) -> None:
        self.dialog.get_by_role("button", name="Сохранить", exact=True).click()

    def expect_dictionary_save_disabled(self) -> None:
        """Кнопка «Сохранить» в диалоге справочника недоступна (имя пустое или из пробелов)."""
        expect(self.dialog.get_by_role("button", name="Сохранить", exact=True)).to_be_disabled()

    def close_dictionary_dialog(self) -> None:
        """Закрывает диалог справочника по кнопке «Отмена»."""
        self.dialog.get_by_role("button", name="Отмена", exact=True).click()

    def expect_dictionary_item(self, name: str) -> None:
        expect(self._dictionary_row(name)).to_be_visible()

    def expect_dictionary_item_absent(self, name: str) -> None:
        expect(self._dictionary_row(name)).to_have_count(0)

    def expect_dictionary_items_count(self, count: int) -> None:
        # у каждого элемента справочника ровно одна иконка редактирования
        expect(self.page.locator(self.EDIT_ICON_BUTTON)).to_have_count(count)

    def click_edit_dictionary(self, name: str) -> None:
        self._dictionary_row(name).locator(self.EDIT_ICON_BUTTON).click()

    def click_delete_dictionary(self, name: str) -> None:
        self._dictionary_row(name).locator(self.DELETE_ICON_BUTTON).click()

    def expect_delete_button_disabled(self, name: str) -> None:
        expect(self._dictionary_row(name).locator(self.DELETE_ICON_BUTTON)).to_be_disabled()

    def expect_delete_button_enabled(self, name: str) -> None:
        expect(self._dictionary_row(name).locator(self.DELETE_ICON_BUTTON)).to_be_enabled()

    # --- Обзор ---

    def expect_bugs_count(self, count: int) -> None:
        expect(self.page.get_by_text("Багов:")).to_contain_text(str(count))

    # --- Вспомогательное ---

    @property
    def dialog(self) -> Locator:
        return self.page.get_by_role("dialog")

    def _member_row(self, login: str) -> Locator:
        return self.page.locator(f'tr:has-text("{login}")').first

    def _dictionary_row(self, name: str) -> Locator:
        return self.page.locator(f'tr:has-text("{name}")').first
