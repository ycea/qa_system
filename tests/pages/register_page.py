from playwright.sync_api import Page, expect

from pages.base_page import BasePage


class RegisterPage(BasePage):
    LOGIN_INPUT = 'input[name="name"]'
    EMAIL_INPUT = 'input[name="email"]'
    PASSWORD_INPUT = 'input[name="password"]'
    CONFIRM_PASSWORD_INPUT = 'input[name="password_confirmation"]'
    SUBMIT_BUTTON = 'button[type="submit"]'
    ERROR_ALERT = ".MuiAlert-root"
    LOGIN_LINK = 'a[href="/login"]'

    def open(self) -> None:
        self.page.goto("/register")

    def fill_login(self, value: str) -> None:
        self.page.fill(self.LOGIN_INPUT, value)

    def fill_email(self, value: str) -> None:
        self.page.fill(self.EMAIL_INPUT, value)

    def fill_password(self, value: str) -> None:
        self.page.fill(self.PASSWORD_INPUT, value)

    def fill_confirm_password(self, value: str) -> None:
        self.page.fill(self.CONFIRM_PASSWORD_INPUT, value)

    def submit(self) -> None:
        self.page.click(self.SUBMIT_BUTTON)

    def expect_error(self, message: str | None = None) -> None:
        expect(self.page.locator(self.ERROR_ALERT)).to_be_visible()
        if message:
            expect(self.page.locator(self.ERROR_ALERT)).to_contain_text(message)

    def expect_no_error(self) -> None:
        expect(self.page.locator(self.ERROR_ALERT)).to_be_hidden()
    def expect_still_on_page(self) -> None:
        expect(self.page).to_have_url("/register")
    def expect_redirect_to_home(self) -> None:
        expect(self.page).to_have_url("/")