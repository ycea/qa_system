"""E2E-01..E2E-03 — сквозные сценарии администратора и участника."""

import pytest


@pytest.mark.e2e
class TestE2e01Admin:
    """E2E-01 — сценарий администратора проекта."""

    def test_admin_full_flow(self, page, logged_in_user, home_page, project_page, unique_suffix):
        """TC-01: создание проекта → страница проекта → справочники."""
        name = f"E2E Admin {unique_suffix}"

        home_page.open()
        home_page.click_create_project()
        home_page.fill_project_name(name)
        home_page.fill_project_description("E2E test description")
        home_page.submit_create_project()
        home_page.expect_project_card_with_title(name)

        home_page.click_project_card(name)
        project_page.expect_project_opened(name)
        project_page.expect_dictionaries_tab_visible()

        project_page.click_dictionaries_tab()
        project_page.expect_dictionaries_view_opened()

    def test_admin_used_dictionary_not_deleted(
        self, page, logged_in_user, home_page, project_page, unique_suffix
    ):
        """TC-02: дефолтный статус проекта удалить нельзя."""
        name = f"E2E Dict {unique_suffix}"

        home_page.open()
        home_page.click_create_project()
        home_page.fill_project_name(name)
        home_page.submit_create_project()
        home_page.click_project_card(name)
        project_page.expect_project_opened(name)

        project_page.click_dictionaries_tab()
        project_page.click_dictionary_type("statuses")
        project_page.expect_delete_button_disabled("Новый")


@pytest.mark.e2e
class TestE2e02Participant:
    """E2E-02 — участник работает с баг-репортами."""

    def test_participant_flow(self, page, logged_in_user, home_page, bugs_page, unique_suffix):
        """TC-01: создание проекта и бага, баг появляется в списке."""
        name = f"E2E Bugs {unique_suffix}"

        home_page.open()
        home_page.click_create_project()
        home_page.fill_project_name(name)
        home_page.submit_create_project()
        home_page.click_project_card(name)
        project_id = page.url.rstrip("/").split("/")[-1]

        bugs_page.open(project_id)
        bugs_page.click_create()
        bugs_page.fill_bug_form(
            title="E2E Bug",
            description="Описание",
            steps="Шаги",
            priority="normal",
            severity="Major",
        )
        bugs_page.submit_form()

        bugs_page.expect_bug_in_table("E2E Bug")


@pytest.mark.e2e
class TestE2e03ParticipantClose:
    """E2E-03 — участник закрывает баг."""

    def test_participant_close_bug_flow(
        self, page, logged_in_user, home_page, bugs_page, unique_suffix
    ):
        """TC-01: создание бага и закрытие его через смену статуса."""
        name = f"E2E Close {unique_suffix}"

        home_page.open()
        home_page.click_create_project()
        home_page.fill_project_name(name)
        home_page.submit_create_project()
        home_page.click_project_card(name)
        project_id = page.url.rstrip("/").split("/")[-1]

        bugs_page.open(project_id)
        bugs_page.click_create()
        bugs_page.fill_bug_form(
            title="Close me",
            description="Описание",
            steps="Шаги",
            priority="normal",
            severity="Major",
        )
        bugs_page.submit_form()

        bugs_page.change_status_in_table("Close me", "Закрыт")
        bugs_page.click_bug_row("Close me")
        bugs_page.expect_history_record("Статус")
