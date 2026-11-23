"""REPT-01 — аналитический отчёт по проекту."""

import pytest


@pytest.mark.reports
class TestRept01:
    def test_report_built_for_project_and_period(
        self, logged_in_user, created_project, reports_page
    ):
        """TC-REPT-01: отчёт формируется после выбора проекта и периода."""
        reports_page.open()
        reports_page.expect_prompt()

        reports_page.select_project(created_project["name"])
        reports_page.select_period("Неделя")

        reports_page.expect_report_table()
        reports_page.expect_report_headers()

    def test_period_switch_rebuilds_report(self, logged_in_user, created_project, reports_page):
        """TC-REPT-01: смена периода перестраивает отчёт."""
        reports_page.open()
        reports_page.select_project(created_project["name"])

        for period in ("День", "Месяц", "Неделя"):
            reports_page.select_period(period)
            reports_page.expect_report_table()
