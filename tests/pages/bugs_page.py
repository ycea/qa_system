from playwright.sync_api import Page, expect

from pages.base_page import BasePage


class BugsPage(BasePage):
    """Stub page for bug management — feature not yet implemented.

    Tests using this page are expected to fail because the /projects/:id/bugs
    route does not exist in the frontend.
    """

    def open(self, project_id: int | str) -> None:
        self.page.goto(f"/projects/{project_id}/bugs")

    def expect_bugs_table(self) -> None:
        expect(self.page.locator("table")).to_be_visible()

    def expect_create_bug_form(self) -> None:
        expect(self.page.locator('form')).to_be_visible()

    def expect_bug_details(self, bug_id: int | str) -> None:
        self.page.goto(f"/projects/{self.page.locator('body').inner_text()}/bugs/{bug_id}")
        expect(self.page.locator("h1, h2, h3")).to_be_visible()

    def expect_404(self) -> None:
        expect(self.page.locator("text=404")).to_be_visible()