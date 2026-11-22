"""METR-01..METR-03 — пользовательские метрики проекта."""

import pytest


@pytest.mark.metrics
class TestMetr01:
    def test_create_numeric_metric(self, logged_in_user, created_project, metrics_page):
        """TC-METR-01: создание числовой метрики."""
        metrics_page.open(created_project["id"])
        metrics_page.click_create_metric()
        metrics_page.fill_metric("Время исправления")
        metrics_page.submit_metric()
        metrics_page.expect_metric_item("Время исправления")

    def test_create_categorical_metric(self, logged_in_user, created_project, metrics_page):
        """TC-METR-01: создание категориальной метрики с вариантами."""
        metrics_page.open(created_project["id"])
        metrics_page.click_create_metric()
        metrics_page.fill_metric("Компонент")
        metrics_page.select_metric_type("Категориальная")
        metrics_page.fill_metric_options("UI, API")
        metrics_page.submit_metric()
        metrics_page.expect_metric_item("Компонент")

    def test_back_to_project(self, logged_in_user, created_project, metrics_page, project_page):
        """Дефект 1: возврат к проекту со страницы метрик."""
        metrics_page.open(created_project["id"])
        metrics_page.click_back_to_project()
        project_page.expect_project_opened(created_project["name"])


@pytest.mark.metrics
class TestMetr03:
    def test_analytics_section_and_export(self, logged_in_user, created_project, metrics_page):
        """TC-METR-03: раздел аналитики и ссылка экспорта доступны."""
        metrics_page.open(created_project["id"])
        metrics_page.click_create_metric()
        metrics_page.fill_metric("Время")
        metrics_page.submit_metric()
        metrics_page.expect_metric_item("Время")

        metrics_page.expect_analytics_section()
        metrics_page.expect_metric_card("Время")
        metrics_page.expect_export_link()

    def test_per_metric_chart(self, page, logged_in_user, created_project, metrics_page, bugs_page):
        """TC-METR-03: отдельный график на метрику появляется после ввода значения."""
        metrics_page.open(created_project["id"])
        metrics_page.click_create_metric()
        metrics_page.fill_metric("Время")
        metrics_page.submit_metric()
        metrics_page.expect_metric_item("Время")
        # Пока значений нет — у метрики нет данных для графика.
        metrics_page.expect_no_chart_data()

        # Заполняем значение метрики на баг-репорте.
        bugs_page.open(created_project["id"])
        bugs_page.click_create()
        bugs_page.fill_bug_form(
            title="Баг с метрикой",
            description="Описание",
            steps="Шаги",
            priority="normal",
            severity="Major",
        )
        bugs_page.submit_form()
        bugs_page.click_bug_row("Баг с метрикой")
        bugs_page.set_metric_value("Время", "5")

        metrics_page.open(created_project["id"])
        metrics_page.expect_chart_for("Время")
