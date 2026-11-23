from playwright.sync_api import expect

from pages.base_page import BasePage


class DashboardPage(BasePage):
    """Страница дашборда проекта (/projects/:id/dashboard)."""

    BACK = "← Назад к проекту"

    def open(self, project_id: int | str) -> None:
        self.page.goto(f"/projects/{project_id}/dashboard")

    def expect_back_to_project(self) -> None:
        expect(self.page.get_by_role("button", name=self.BACK, exact=True)).to_be_visible()

    def click_back_to_project(self) -> None:
        self.page.get_by_role("button", name=self.BACK, exact=True).click()

    def expect_widget(self, label: str, value: int | str) -> None:
        card = self.page.locator(f'.MuiCard-root:has-text("{label}")')
        expect(card).to_be_visible()
        expect(card).to_contain_text(str(value))

    def expect_total_bugs_widget(self, value: int | str) -> None:
        self.expect_widget("Всего багов", value)

    def expect_open_bugs_widget(self, value: int | str) -> None:
        self.expect_widget("Открытые баги", value)

    def expect_closed_week_widget(self, value: int | str) -> None:
        self.expect_widget("Закрытые за неделю", value)

    def expect_404(self) -> None:
        expect(self.page.locator("text=404")).to_be_visible()
