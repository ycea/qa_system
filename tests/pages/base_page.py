import re

from playwright.sync_api import Page, expect


class BasePage:
    def __init__(self, page: Page) -> None:
        self.page = page

    def go_to(self, path: str) -> None:
        self.page.goto(path)

    def expect_url_to_contain(self, fragment: str) -> None:
        """Проверяет, что URL страницы содержит указанный фрагмент.

        fragment — обычный текст (не регулярка и не glob): Playwright сравнивает строку
        строго на равенство и не поддерживает glob в to_have_url, поэтому фрагмент
        оборачивается в регулярку через re.escape.
        """
        expect(self.page).to_have_url(re.compile(re.escape(fragment)))

    def expect_url_matches(self, pattern: str) -> None:
        r"""Проверяет, что URL страницы соответствует регулярному выражению.

        pattern — строка с регуляркой. Для backslash-классов обязательна raw-строка,
        иначе выражение молча ломается (\b превращается в backspace, \d даёт SyntaxWarning):
            expect_url_matches(r"/projects/\d+$")
        """
        expect(self.page).to_have_url(re.compile(pattern))

    def expect_url_equals(self, url: str) -> None:
        """Проверяет точное совпадение URL (относительный путь разрешается от base_url)."""
        expect(self.page).to_have_url(url)

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
