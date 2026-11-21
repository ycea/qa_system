"""DASH-01 — Информационная панель о проекте (尚 не реализован)."""

import pytest


@pytest.mark.dashboard
class TestDash01:
    """DASH-01 — Информационная панель о проекте."""

    def test_widgets_displayed(self, page, dashboard_page):
        """TC-01-DASH-01-SUCCESS_WIDGETS_DISPLAYED"""
        dashboard_page.open(1)
        dashboard_page.expect_total_bugs_widget()

    def test_total_bugs_widget(self, page, dashboard_page):
        """TC-02-DASH-01-SUCCESS_TOTAL_BUGS_WIDGET"""
        dashboard_page.open(1)
        dashboard_page.expect_total_bugs_widget()

    def test_open_bugs_widget(self, page, dashboard_page):
        """TC-03-DASH-01-SUCCESS_OPEN_BUGS_WIDGET"""
        dashboard_page.open(1)
        dashboard_page.expect_open_bugs_widget()

    def test_closed_week_widget(self, page, dashboard_page):
        """TC-04-DASH-01-SUCCESS_CLOSED_WEEK_WIDGET"""
        dashboard_page.open(1)
        dashboard_page.expect_closed_week_widget()

    def test_widgets_match_bug_list(self, page, dashboard_page):
        """TC-05-DASH-01-SUCCESS_WIDGETS_MATCH_BUG_LIST"""
        dashboard_page.open(1)
        dashboard_page.expect_total_bugs_widget()

    def test_empty_project_state(self, page, dashboard_page):
        """TC-06-DASH-01-SUCCESS_EMPTY_PROJECT_STATE"""
        dashboard_page.open(1)
        dashboard_page.expect_total_bugs_widget()
