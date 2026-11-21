from playwright.sync_api import expect

from pages.base_page import BasePage


class ReportsPage(BasePage):
    """Stub page for reports — feature not yet implemented.

    Tests using this page are expected to fail because the reports routes
    do not exist in the frontend.
    """

    def open(self) -> None:
        self.page.goto("/reports")

    def expect_project_select(self) -> None:
        expect(self.page.locator("select")).to_be_visible()

    def expect_period_select(self) -> None:
        expect(self.page.locator('select[name="period"]')).to_be_visible()

    def expect_report_table(self) -> None:
        expect(self.page.locator("table")).to_be_visible()

    def expect_export_button(self) -> None:
        expect(self.page.locator("text=Экспорт")).to_be_visible()

    def expect_404(self) -> None:
        expect(self.page.locator("text=404")).to_be_visible()
