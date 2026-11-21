from playwright.sync_api import expect

from pages.base_page import BasePage


class LoginPage(BasePage):
    LOGIN_INPUT = 'input[name="name"]'
    PASSWORD_INPUT = 'input[name="password"]'
    SUBMIT_BUTTON = 'button[type="submit"]'
    ERROR_ALERT = ".MuiAlert-root"
    REGISTER_LINK = 'a[href="/register"]'

    def open(self) -> None:
        self.page.goto("/login")

    def fill_login(self, value: str) -> None:
        self.page.fill(self.LOGIN_INPUT, value)

    def fill_password(self, value: str) -> None:
        self.page.fill(self.PASSWORD_INPUT, value)

    def submit(self) -> None:
        self.page.click(self.SUBMIT_BUTTON)

    def expect_error(self, message: str | None = None) -> None:
        expect(self.page.locator(self.ERROR_ALERT)).to_be_visible()
        if message:
            expect(self.page.locator(self.ERROR_ALERT)).to_contain_text(message)

    def expect_no_error(self) -> None:
        expect(self.page.locator(self.ERROR_ALERT)).to_be_hidden()

    def expect_still_on_page(self) -> None:
        expect(self.page).to_have_url("/login")

    def expect_redirect_to_home(self) -> None:
        expect(self.page).to_have_url("/")

    def login(self, credentials: dict[str, str]) -> None:
        """Полный вход в систему: открыть форму, заполнить, отправить, дождаться редиректа."""
        self.open()
        self.fill_login(credentials["login"])
        self.fill_password(credentials["password"])
        self.submit()
        self.expect_redirect_to_home()
