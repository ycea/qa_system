from playwright.sync_api import Page, expect

from pages.base_page import BasePage


class ProjectPage(BasePage):
    TEAM_TAB = 'text=Команда'
    DICTIONARIES_TAB = 'text=Справочники'
    OVERVIEW_TAB = 'text=Обзор'
    ADD_MEMBER_BUTTON = 'text=Добавить участника'
    MEMBER_LOGIN_INPUT = 'input[name="Логин пользователя"]'
    MEMBER_ROLE_SELECT = 'select[name="Роль"]'
    ADD_MEMBER_SUBMIT = 'text=Добавить'
    MEMBERS_TABLE = "table"
    MEMBER_ROW = "tr"
    DELETE_BUTTON = 'button[color="error"]'
    CONFIRM_DELETE_INPUT = 'input[label*="Введите"]'
    CONFIRM_DELETE_BUTTON = 'button[color="error"]'
    DICTIONARY_TYPE_TABS = {
        "statuses": 'text=Статусы',
        "priorities": 'text=Приоритеты',
        "severities": 'text=Серьёзности',
    }
    ADD_DICTIONARY_BUTTON = 'text=Добавить в'
    DICTIONARY_NAME_INPUT = 'input[name="Название"]'
    DICTIONARY_SAVE_BUTTON = 'text=Сохранить'
    DICTIONARY_TABLE_ROW = "tr"
    EDIT_BUTTON = 'button[title="Редактировать"]'
    DELETE_DICTIONARY_BUTTON = 'button[color="error"]'
    BUGS_COUNT_TEXT = 'text=Багов:'

    def open_project(self, project_id: int | str) -> None:
        self.page.goto(f"/projects/{project_id}")

    def click_team_tab(self) -> None:
        self.page.click(self.TEAM_TAB)

    def click_dictionaries_tab(self) -> None:
        self.page.click(self.DICTIONARIES_TAB)

    def click_overview_tab(self) -> None:
        self.page.click(self.OVERVIEW_TAB)

    def expect_team_table_visible(self) -> None:
        expect(self.page.locator(self.MEMBERS_TABLE)).to_be_visible()

    def expect_member_with_role(self, login: str, role: str) -> None:
        row = self.page.locator(f'{self.MEMBER_ROW}:has-text("{login}")')
        expect(row).to_contain_text(role)

    def expect_no_management_controls(self) -> None:
        expect(self.page.locator(self.DELETE_BUTTON)).to_be_hidden()

    def expect_delete_button_visible(self) -> None:
        expect(self.page.locator(self.DELETE_BUTTON)).to_be_visible()

    def click_add_member(self) -> None:
        self.page.click(self.ADD_MEMBER_BUTTON)

    def fill_member_login(self, value: str) -> None:
        self.page.fill(self.MEMBER_LOGIN_INPUT, value)

    def select_member_role(self, value: str) -> None:
        self.page.select_option(self.MEMBER_ROLE_SELECT, value)

    def submit_add_member(self) -> None:
        self.page.click(self.ADD_MEMBER_SUBMIT)

    def expect_error(self, message: str | None = None) -> None:
        alert = self.page.locator(".MuiAlert-root")
        expect(alert).to_be_visible()
        if message:
            expect(alert).to_contain_text(message)

    def click_delete_member(self, login: str) -> None:
        row = self.page.locator(f'{self.MEMBER_ROW}:has-text("{login}")')
        row.locator(self.DELETE_BUTTON).click()

    def confirm_delete(self, project_name: str) -> None:
        self.page.fill(self.CONFIRM_DELETE_INPUT, project_name)
        self.page.click(self.CONFIRM_DELETE_BUTTON)

    def expect_dictionaries_tab_hidden(self) -> None:
        expect(self.page.locator(self.DICTIONARIES_TAB)).to_be_hidden()

    def expect_dictionaries_tab_visible(self) -> None:
        expect(self.page.locator(self.DICTIONARIES_TAB)).to_be_visible()

    def click_dictionary_type(self, dict_type: str) -> None:
        self.page.click(self.DICTIONARY_TYPE_TABS[dict_type])

    def click_add_dictionary(self) -> None:
        self.page.click(self.ADD_DICTIONARY_BUTTON)

    def fill_dictionary_name(self, value: str) -> None:
        self.page.fill(self.DICTIONARY_NAME_INPUT, value)

    def submit_dictionary(self) -> None:
        self.page.click(self.DICTIONARY_SAVE_BUTTON)

    def expect_dictionary_item(self, name: str) -> None:
        expect(self.page.locator(f'{self.DICTIONARY_TABLE_ROW}:has-text("{name}")')).to_be_visible()

    def click_edit_dictionary(self, name: str) -> None:
        row = self.page.locator(f'{self.DICTIONARY_TABLE_ROW}:has-text("{name}")')
        row.locator(self.EDIT_BUTTON).click()

    def click_delete_dictionary(self, name: str) -> None:
        row = self.page.locator(f'{self.DICTIONARY_TABLE_ROW}:has-text("{name}")')
        row.locator(self.DELETE_DICTIONARY_BUTTON).click()

    def expect_delete_button_disabled(self, name: str) -> None:
        row = self.page.locator(f'{self.DICTIONARY_TABLE_ROW}:has-text("{name}")')
        expect(row.locator(self.DELETE_DICTIONARY_BUTTON)).to_be_disabled()

    def expect_bugs_count(self, count: int) -> None:
        expect(self.page.locator(self.BUGS_COUNT_TEXT)).to_contain_text(str(count))