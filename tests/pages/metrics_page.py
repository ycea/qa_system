from playwright.sync_api import Locator, expect

from pages.base_page import BasePage


class MetricsPage(BasePage):
    """Страница метрик проекта (/projects/:id/metrics)."""

    BACK = "← Назад к проекту"
    CREATE = "Создать метрику"
    EXPORT = "Экспорт в Excel"

    def open(self, project_id: int | str) -> None:
        self.page.goto(f"/projects/{project_id}/metrics")

    @property
    def dialog(self) -> Locator:
        return self.page.get_by_role("dialog")

    def expect_back_to_project(self) -> None:
        expect(self.page.get_by_role("button", name=self.BACK, exact=True)).to_be_visible()

    def click_back_to_project(self) -> None:
        self.page.get_by_role("button", name=self.BACK, exact=True).click()

    # --- METR-01: управление метриками ---

    def click_create_metric(self) -> None:
        self.page.get_by_role("button", name=self.CREATE, exact=True).click()

    def fill_metric(self, name: str) -> None:
        self.dialog.get_by_label("Название метрики").fill(name)

    def select_metric_type(self, type_label: str) -> None:
        self.dialog.get_by_label("Тип метрики").click()
        self.page.get_by_role("option", name=type_label).click()

    def fill_metric_options(self, options: str) -> None:
        self.dialog.get_by_label("Варианты (через запятую)").fill(options)

    def submit_metric(self) -> None:
        self.dialog.get_by_role("button", name="Сохранить", exact=True).click()

    def expect_metric_item(self, name: str) -> None:
        expect(self.page.locator(f'tr:has-text("{name}")')).to_be_visible()

    def expect_metric_absent(self, name: str) -> None:
        expect(self.page.locator(f'tr:has-text("{name}")')).to_have_count(0)

    def click_edit_metric(self, name: str) -> None:
        self.page.locator(f'tr:has-text("{name}")').locator(
            'button:has([data-testid="EditIcon"])'
        ).click()

    def click_delete_metric(self, name: str) -> None:
        self.page.locator(f'tr:has-text("{name}")').locator(
            'button:has([data-testid="DeleteIcon"])'
        ).click()

    # --- METR-03: аналитика и экспорт ---

    def expect_analytics_section(self) -> None:
        expect(self.page.get_by_text("Аналитика")).to_be_visible()

    def expect_metric_card(self, name: str) -> None:
        expect(self.page.locator(f'.MuiCard-root:has-text("{name}")')).to_be_visible()

    def expect_charts_count(self, count: int) -> None:
        expect(self.page.locator("svg.recharts-surface")).to_have_count(count)

    def expect_chart_for(self, name: str) -> None:
        """График внутри карточки указанной метрики."""
        card = self.page.locator(f'.MuiCard-root:has-text("{name}")')
        expect(card.locator("svg.recharts-surface")).to_be_visible()

    def expect_no_chart_data(self) -> None:
        expect(self.page.get_by_text("Нет данных для графика.").first).to_be_visible()

    def expect_export_link(self, href: str | None = None) -> None:
        link = self.page.get_by_role("link", name=self.EXPORT)
        expect(link).to_be_visible()
        if href:
            expect(link).to_have_attribute("href", href)

    def expect_404(self) -> None:
        expect(self.page.locator("text=404")).to_be_visible()
