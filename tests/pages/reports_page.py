from playwright.sync_api import expect

from pages.base_page import BasePage


class ReportsPage(BasePage):
    """Страница отчётов (/reports)."""

    HEADERS = ("Всего багов", "Закрыто", "Процент закрытых", "Среднее время жизни (ч)")

    def open(self) -> None:
        self.page.goto("/reports")

    def select_project(self, project_name: str) -> None:
        self.page.get_by_role("combobox", name="Проект").click()
        self.page.get_by_role("option", name=project_name).click()

    def select_period(self, period_label: str) -> None:
        self.page.get_by_role("combobox", name="Период").click()
        self.page.get_by_role("option", name=period_label).click()

    def expect_prompt(self) -> None:
        expect(self.page.get_by_text("Выберите проект для построения отчёта.")).to_be_visible()

    def expect_report_table(self) -> None:
        expect(self.page.locator("table")).to_be_visible()

    def expect_report_headers(self) -> None:
        for header in self.HEADERS:
            expect(self.page.get_by_role("columnheader", name=header)).to_be_visible()

    def expect_report_row(self, total: int, closed: int) -> None:
        row = self.page.locator("table tbody tr").first
        expect(row).to_contain_text(str(total))
        expect(row).to_contain_text(str(closed))

    def expect_404(self) -> None:
        expect(self.page.locator("text=404")).to_be_visible()
