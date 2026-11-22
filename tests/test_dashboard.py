"""DASH-01 — информационная панель проекта."""

import pytest


@pytest.mark.dashboard
class TestDash01:
    def test_widgets_displayed(self, logged_in_user, created_project, dashboard_page):
        """TC-01-DASH-01: виджеты статистики отображаются с нулевыми значениями."""
        dashboard_page.open(created_project["id"])
        dashboard_page.expect_total_bugs_widget(0)
        dashboard_page.expect_open_bugs_widget(0)
        dashboard_page.expect_closed_week_widget(0)

    def test_back_to_project(self, logged_in_user, created_project, dashboard_page, project_page):
        """Дефект 1: возврат к проекту с дашборда."""
        dashboard_page.open(created_project["id"])
        dashboard_page.click_back_to_project()
        project_page.expect_project_opened(created_project["name"])
