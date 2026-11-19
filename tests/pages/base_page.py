from playwright.sync_api import Page, expect


class BasePage:
    def __init__(self, page: Page) -> None:
        self.page = page

    def go_to(self, path: str) -> None:
        self.page.goto(path)

    def expect_url_to_contain(self, fragment: str) -> None:
        expect(self.page).to_have_url(f"*{fragment}*")

    def expect_visible(self, locator: str) -> None:
        expect(self.page.locator(locator)).to_be_visible()

    def expect_hidden(self, locator: str) -> None:
        expect(self.page.locator(locator)).to_be_hidden()

    def expect_text(self, locator: str, text: str) -> None:
        expect(self.page.locator(locator)).to_contain_text(text)

    def click_text(self, text: str) -> None:
        self.page.click(f"text={text}")

    def fill_input(self, name: str, value: str) -> None:
        self.page.fill(f'input[name="{name}"]', value)

    def fill_textarea(self, name: str, value: str) -> None:
        self.page.fill(f'textarea[name="{name}"]', value)

    def submit_form(self) -> None:
        self.page.click('button[type="submit"]')