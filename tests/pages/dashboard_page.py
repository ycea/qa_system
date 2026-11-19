from playwright.sync_api import Page, expect

from pages.base_page import BasePage


class DashboardPage(BasePage):
    """Stub page for dashboard widgets — feature not yet implemented.

    Tests using this page are expected to fail because the dashboard routes
    do not exist in the frontend.
    """

    def open(self, project_id: int | str) -> None:
        self.page.goto(f"/projects/{project_id}/dashboard")

    def expect_total_bugs_widget(self) -> None:
        expect(self.page.locator("text=Всего багов")).to_be_visible()

    def expect_open_bugs_widget(self) -> None:
        expect(self.page.locator("text=Открытые баги")).to_be_visible()

    def expect_closed_week_widget(self) -> None:
        expect(self.page.locator("text=Закрытые за неделю")).to_be_visible()

    def expect_404(self) -> None:
        expect(self.page.locator("text=404")).to_be_visible()