"""BUG-01..BUG-08 — модуль управления багами."""

import pytest


@pytest.mark.bugs
class TestBug01Creation:
    def test_create_bug_appears_in_list(self, logged_in_user, created_project, bugs_page):
        """TC-BUG-01: созданный баг появляется в списке."""
        bugs_page.open(created_project["id"])
        bugs_page.click_create()
        bugs_page.fill_bug_form(
            title="Краш при входе",
            description="Описание дефекта",
            steps="Открыть, войти",
            priority="normal",
            severity="Major",
        )
        bugs_page.submit_form()
        bugs_page.expect_bug_in_table("Краш при входе")

    def test_empty_title_shows_error(self, logged_in_user, created_project, bugs_page):
        """TC-BUG-01-FAIL: пустой заголовок даёт ошибку."""
        bugs_page.open(created_project["id"])
        bugs_page.click_create()
        bugs_page.submit_form()
        bugs_page.expect_error()

    def test_back_to_project(self, logged_in_user, created_project, bugs_page, project_page):
        """Дефект 1: возврат к проекту со страницы багов."""
        bugs_page.open(created_project["id"])
        bugs_page.click_back_to_project()
        project_page.expect_project_opened(created_project["name"])


@pytest.mark.bugs
class TestBug02Details:
    def test_row_click_opens_details(self, logged_in_user, created_project, bugs_page):
        """Дефект 3: переход в баг кликом по строке (не только по заголовку)."""
        bugs_page.open(created_project["id"])
        bugs_page.click_create()
        bugs_page.fill_bug_form(
            title="Баг для деталей",
            description="Описание",
            steps="Шаги",
            priority="normal",
            severity="Major",
        )
        bugs_page.submit_form()

        bugs_page.click_bug_row("Баг для деталей")

        bugs_page.expect_details("Баг для деталей")
        bugs_page.expect_history_section()
        bugs_page.expect_back_to_list()


@pytest.mark.bugs
class TestBug06StatusInTable:
    def test_change_status_from_table(self, logged_in_user, created_project, bugs_page):
        """BUG-06: смена статуса прямо из таблицы."""
        bugs_page.open(created_project["id"])
        bugs_page.click_create()
        bugs_page.fill_bug_form(
            title="Баг со статусом",
            description="Описание",
            steps="Шаги",
            priority="normal",
            severity="Major",
        )
        bugs_page.submit_form()

        bugs_page.change_status_in_table("Баг со статусом", "В работе")
        bugs_page.click_bug_row("Баг со статусом")
        bugs_page.expect_history_record("Статус")


@pytest.mark.bugs
class TestBug07Comments:
    def test_add_comment(self, logged_in_user, created_project, bugs_page):
        """BUG-07: добавление комментария к багу."""
        bugs_page.open(created_project["id"])
        bugs_page.click_create()
        bugs_page.fill_bug_form(
            title="Баг с комментарием",
            description="Описание",
            steps="Шаги",
            priority="normal",
            severity="Major",
        )
        bugs_page.submit_form()

        bugs_page.click_bug_row("Баг с комментарием")
        bugs_page.add_comment("Первый комментарий")
        bugs_page.expect_comment("Первый комментарий")


@pytest.mark.bugs
class TestBug08Filters:
    def test_search_and_reset(self, logged_in_user, created_project, bugs_page):
        """BUG-08: поиск по заголовку и сброс фильтров."""
        bugs_page.open(created_project["id"])
        for title in ("Login broken", "Payment issue"):
            bugs_page.click_create()
            bugs_page.fill_bug_form(
                title=title,
                description="Описание",
                steps="Шаги",
                priority="normal",
                severity="Major",
            )
            bugs_page.submit_form()

        bugs_page.search("Login")
        bugs_page.expect_bug_in_table("Login broken")
        bugs_page.expect_bug_absent("Payment issue")

        bugs_page.reset_filters()
        bugs_page.expect_bug_in_table("Payment issue")
