from playwright.sync_api import expect

from pages.base_page import BasePage


class MetricsPage(BasePage):
    """Stub page for metrics management — feature not yet implemented.

    Tests using this page are expected to fail because the metrics routes
    do not exist in the frontend.
    """

    def open(self, project_id: int | str) -> None:
        self.page.goto(f"/projects/{project_id}/metrics")

    def expect_metrics_form(self) -> None:
        expect(self.page.locator("form")).to_be_visible()

    def expect_metrics_list(self) -> None:
        expect(self.page.locator("table, ul, .MuiList-root")).to_be_visible()

    def expect_analytics_section(self) -> None:
        expect(self.page.locator("text=Аналитика")).to_be_visible()

    def expect_404(self) -> None:
        expect(self.page.locator("text=404")).to_be_visible()
