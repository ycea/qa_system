from playwright.sync_api import expect

from pages.base_page import BasePage


class HomePage(BasePage):
    CREATE_PROJECT_BUTTON = "text=Создать проект"
    PROJECT_NAME_INPUT = 'input[name="name"]'
    PROJECT_DESCRIPTION_INPUT = 'textarea[name="description"]'
    CREATE_SUBMIT_BUTTON = 'button[name="create_project"]'
    PROJECT_CARD = ".MuiCard-root"
    PROJECT_CARD_TITLE = ".MuiCard-root h6"
    PROJECT_CARD_OPEN_BUGS = '.MuiCard-root:has-text("Открытых багов:")'
    EMPTY_LIST_TEXT = "text=У вас пока нет проектов"
    ERROR_ALERT = ".MuiAlert-root"

    def open(self) -> None:
        self.page.goto("/")

    def click_create_project(self) -> None:
        self.page.click(self.CREATE_PROJECT_BUTTON)

    def fill_project_name(self, value: str) -> None:
        self.page.fill(self.PROJECT_NAME_INPUT, value)

    def fill_project_description(self, value: str) -> None:
        self.page.fill(self.PROJECT_DESCRIPTION_INPUT, value)

    def expect_description_length(self, length: int) -> None:
        """Фактическая длина введённого описания (UI ограничивает 500 символов)."""
        actual = self.page.input_value(self.PROJECT_DESCRIPTION_INPUT)
        assert len(actual) == length, f"Ожидалось {length} символов, получено {len(actual)}"

    def submit_create_project(self) -> None:
        self.page.click(self.CREATE_SUBMIT_BUTTON)

    def expect_project_list_empty(self) -> None:
        expect(self.page.locator(self.EMPTY_LIST_TEXT)).to_be_visible()

    def expect_project_card_count(self, count: int) -> None:
        expect(self.page.locator(self.PROJECT_CARD)).to_have_count(count)

    def expect_project_card_with_title(self, title: str) -> None:
        expect(self.page.locator(f'{self.PROJECT_CARD}:has-text("{title}")')).to_be_visible()

    def expect_no_project_card(self, title: str) -> None:
        """Явная проверка, что проекта с таким названием в списке нет."""
        expect(self.page.locator(f'{self.PROJECT_CARD}:has-text("{title}")')).to_have_count(0)

    def expect_no_project_cards(self) -> None:
        """Явная проверка, что карточек проектов нет вообще."""
        expect(self.page.locator(self.PROJECT_CARD)).to_have_count(0)

    def expect_open_bugs_count_on_card(self, title: str, count: int) -> None:
        card = self.page.locator(f'{self.PROJECT_CARD}:has-text("{title}")')
        expect(card.locator(".MuiTypography-root")).to_contain_text(str(count))

    def click_project_card(self, title: str) -> None:
        self.page.click(f'{self.PROJECT_CARD}:has-text("{title}")')

    def expect_error(self, message: str | None = None) -> None:
        expect(self.page.locator(self.ERROR_ALERT)).to_be_visible()
        if message:
            expect(self.page.locator(self.ERROR_ALERT)).to_contain_text(message)
