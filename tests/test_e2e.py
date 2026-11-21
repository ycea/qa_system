"""E2E-01 через E2E-03 — сквозные сценарии."""

import pytest


@pytest.mark.e2e
class TestE2e01Admin:
    """E2E-01 — Сценарий администратора проекта."""

    def test_admin_full_flow(self, page, logged_in_user, home_page, project_page):
        """TC-01-E2E-01-ADMIN_FULL_FLOW"""
        home_page.click_create_project()
        home_page.fill_project_name("E2E Admin Project")
        home_page.fill_project_description("E2E test description")
        home_page.submit_create_project()
        page.wait_for_url("**/projects/*")
        project_page.click_team_tab()
        project_page.expect_dictionaries_tab_visible()

    def test_admin_used_dictionary_not_deleted(self, page, logged_in_user, home_page, project_page):
        """TC-02-E2E-01-ADMIN_USED_DICTIONARY_NOT_DELETED"""
        home_page.click_project_card("E2E Admin Project")
        page.wait_for_url("**/projects/*")
        project_page.click_dictionaries_tab()
        project_page.click_dictionary_type("statuses")
        project_page.expect_delete_button_visible()


@pytest.mark.e2e
class TestE2e02Participant:
    """E2E-02 — Сценарий участника проекта."""

    def test_participant_flow(self, page, logged_in_user, home_page, bugs_page):
        """TC-01-E2E-02-PARTICIPANT_FLOW"""
        home_page.click_project_card("E2E Admin Project")
        page.wait_for_url("**/projects/*")
        bugs_page.open(page.url.split("/")[-1])
        bugs_page.expect_404()


@pytest.mark.e2e
class TestE2e03ParticipantClose:
    """E2E-03 — Сценарий участника проекта, закрывающего баг."""

    def test_participant_close_bug_flow(self, page, logged_in_user, home_page, bugs_page):
        """TC-01-E2E-03-PARTICIPANT_CLOSE_BUG_FLOW"""
        home_page.click_project_card("E2E Admin Project")
        page.wait_for_url("**/projects/*")
        bugs_page.open(page.url.split("/")[-1])
        bugs_page.expect_404()
