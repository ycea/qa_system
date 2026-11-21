"""REPT-01 — Функционал по созданию отчета (尚 не реализован)."""

import pytest


@pytest.mark.reports
class TestRept01:
    """REPT-01 — Функционал по созданию отчета."""

    def test_project_selection(self, page, reports_page):
        """TC-01-REPT-01-SUCCESS_PROJECT_SELECTION"""
        reports_page.open()
        reports_page.expect_project_select()

    def test_period_selection(self, page, reports_page):
        """TC-02-REPT-01-SUCCESS_PERIOD_SELECTION"""
        reports_page.open()
        reports_page.expect_period_select()

    def test_report_table_fields(self, page, reports_page):
        """TC-03-REPT-01-SUCCESS_REPORT_TABLE_FIELDS"""
        reports_page.open()
        reports_page.expect_report_table()

    def test_data_matches_real(self, page, reports_page):
        """TC-04-REPT-01-SUCCESS_DATA_MATCHES_REAL"""
        reports_page.open()
        reports_page.expect_report_table()

    def test_avg_lifetime_calculation(self, page, reports_page):
        """TC-05-REPT-01-SUCCESS_AVG_LIFETIME_CALCULATION"""
        reports_page.open()
        reports_page.expect_report_table()

    def test_unclosed_bugs_excluded(self, page, reports_page):
        """TC-06-REPT-01-SUCCESS_UNCLOSED_BUGS_EXCLUDED"""
        reports_page.open()
        reports_page.expect_report_table()

    def test_closed_percent_calculation(self, page, reports_page):
        """TC-07-REPT-01-SUCCESS_CLOSED_PERCENT_CALCULATION"""
        reports_page.open()
        reports_page.expect_report_table()

    def test_day_period(self, page, reports_page):
        """TC-08-REPT-01-SUCCESS_DAY_PERIOD"""
        reports_page.open()
        reports_page.expect_report_table()

    def test_week_period(self, page, reports_page):
        """TC-09-REPT-01-SUCCESS_WEEK_PERIOD"""
        reports_page.open()
        reports_page.expect_report_table()

    def test_month_period(self, page, reports_page):
        """TC-10-REPT-01-SUCCESS_MONTH_PERIOD"""
        reports_page.open()
        reports_page.expect_report_table()

    def test_no_closed_bugs(self, page, reports_page):
        """TC-11-REPT-01-SUCCESS_NO_CLOSED_BUGS"""
        reports_page.open()
        reports_page.expect_report_table()

    def test_project_not_selected(self, page, reports_page):
        """TC-12-REPT-01-FAIL_PROJECT_NOT_SELECTED"""
        reports_page.open()
        reports_page.expect_report_table()
