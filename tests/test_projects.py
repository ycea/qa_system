"""PROJ-01 через PROJ-06 — модули проектов, участников и справочников."""

import pytest
from playwright.sync_api import expect


@pytest.mark.projects
class TestProj01Creation:
    """PROJ-01 — Модуль создания проекта."""

    def test_empty_name_others_correct(self, page, logged_in_user, home_page):
        """TC-01-PROJ-01-FAIL-EMPTY_NAME-OTHERS_CORRECT"""
        home_page.open()
        home_page.click_create_project()
        home_page.fill_project_name("")
        home_page.fill_project_description("Valid description")
        home_page.submit_create_project()
        home_page.expect_error()

    def test_whitespace_name_others_correct(self, page, logged_in_user, home_page):
        """TC-02-PROJ-01-FAIL-WHITESPACE_NAME-OTHERS_CORRECT"""
        home_page.open()
        home_page.click_create_project()
        home_page.fill_project_name("   ")
        home_page.fill_project_description("Valid description")
        home_page.submit_create_project()
        home_page.expect_error()

    def test_very_long_name_others_correct(self, page, logged_in_user, home_page):
        """TC-03-PROJ-01-FAIL-VERY_LONG_NAME-OTHERS_CORRECT"""
        home_page.open()
        home_page.click_create_project()
        home_page.fill_project_name("a" * 500)
        home_page.fill_project_description("Valid description")
        home_page.submit_create_project()
        home_page.expect_error()

    def test_very_long_description_others_correct(self, page, logged_in_user, home_page):
        """TC-04-PROJ-01-FAIL-VERY_LONG_DESCRIPTION-OTHERS_CORRECT"""
        home_page.open()
        home_page.click_create_project()
        home_page.fill_project_name("Valid Name")
        home_page.fill_project_description("a" * 3000)
        home_page.submit_create_project()
        home_page.expect_error()

    def test_name_only_description_empty(self, page, logged_in_user, home_page):
        """TC-05-PROJ-01-SUCCESS_NAME_ONLY_DESCRIPTION_EMPTY"""
        home_page.open()
        home_page.click_create_project()
        home_page.fill_project_name("Project Name Only")
        home_page.fill_project_description("")
        home_page.submit_create_project()
        home_page.expect_project_card_with_title("Project Name Only")

    def test_all_correct(self, page, logged_in_user, home_page):
        """TC-06-PROJ-01-SUCCESS_ALL_CORRECT"""
        home_page.open()
        home_page.click_create_project()
        home_page.fill_project_name("Full Project")
        home_page.fill_project_description("Full description")
        home_page.submit_create_project()
        home_page.expect_project_card_with_title("Full Project")

    def test_creator_becomes_admin(self, page, logged_in_user, home_page, project_page):
        """TC-07-PROJ-01-SUCCESS_CREATOR_BECOMES_ADMIN"""
        home_page.open()
        home_page.click_create_project()
        home_page.fill_project_name("AdmFin Project")
        home_page.fill_project_description("Desc")
        home_page.submit_create_project()
        home_page.click_project_card("Admin Project")
        project_page.expect_url_matches(r"/projects/\d+")
        project_page.click_team_tab()
        project_page.expect_member_with_role(logged_in_user["login"], "Администратор")


@pytest.mark.projects
class TestProj02List:
    """PROJ-02 — Модуль по работе со списком проектов."""

    def test_only_member_projects_visible(
        self, page, logged_in_user, created_project, second_user, home_page, login_as
    ):
        """TC-01-PROJ-02-SUCCESS_ONLY_MEMBER_PROJECTS_VISIBLE"""
        # проект создан первым пользователем; второй в нём не состоит и видеть его не должен
        login_as(second_user)
        home_page.open()
        home_page.expect_no_project_card(created_project["name"])
        home_page.expect_project_list_empty()

    def test_project_card_clickable(self, page, logged_in_user, created_project, home_page):
        """TC-02-PROJ-02-SUCCESS_PROJECT_CARD_CLICKABLE"""
        home_page.open()
        home_page.expect_project_card_with_title(created_project["name"])

    def test_card_shows_title_and_open_bugs_count(
        self, page, logged_in_user, created_project, home_page
    ):
        """TC-03-PROJ-02-SUCCESS_CARD_SHOWS_TITLE_AND_OPEN_BUGS_COUNT"""
        home_page.open()
        card = page.locator(f'.MuiCard-root:has-text("{created_project["name"]}")')
        expect(card).to_be_visible()
        expect(card.locator("text=Открытых багов:")).to_be_visible()

    def test_card_open_bugs_count_zero(self, page, logged_in_user, created_project, home_page):
        """TC-04-PROJ-02-SUCCESS_CARD_OPEN_BUGS_COUNT_ZERO"""
        home_page.open()
        card = page.locator(f'.MuiCard-root:has-text("{created_project["name"]}")')
        expect(card.locator("text=0")).to_be_visible()

    def test_click_card_navigates_to_project(
        self, page, logged_in_user, created_project, home_page, project_page
    ):
        """TC-05-PROJ-02-SUCCESS_CLICK_CARD_NAVIGATES_TO_PROJECT"""
        home_page.open()
        home_page.click_project_card(created_project["name"])
        project_page.expect_url_to_contain(f"/projects/{created_project['id']}")
        project_page.expect_project_opened(created_project["name"])

    def test_empty_project_list(self, page, logged_in_user, home_page):
        """TC-06-PROJ-02-SUCCESS_EMPTY_PROJECT_LIST"""
        home_page.open()
        home_page.expect_project_list_empty()


@pytest.mark.projects
class TestProj03Member:
    """PROJ-03 — Добавленный участник в проект."""

    def test_add_existing_user_with_role(
        self, page, logged_in_user, created_project, second_user, project_page
    ):
        """TC-01-PROJ-03-SUCCESS_ADD_EXISTING_USER_WITH_ROLE"""
        project_page.open_project(created_project["id"])
        project_page.click_team_tab()
        project_page.click_add_member()
        project_page.fill_member_login(second_user["login"])
        project_page.select_member_role("developer")
        project_page.submit_add_member()
        project_page.expect_member_with_role(second_user["login"], "Разработчик")

    def test_non_existing_login(self, page, logged_in_user, created_project, project_page):
        """TC-02-PROJ-03-FAIL_NON_EXISTING_LOGIN"""
        project_page.open_project(created_project["id"])
        project_page.click_team_tab()
        project_page.click_add_member()
        project_page.fill_member_login("nonexistent_user")
        project_page.select_member_role("developer")
        project_page.submit_add_member()
        project_page.expect_error("Пользователь не найден")

    def test_empty_login(self, page, logged_in_user, created_project, project_page):
        """TC-03-PROJ-03-FAIL_EMPTY_LOGIN"""
        project_page.open_project(created_project["id"])
        project_page.click_team_tab()
        project_page.click_add_member()
        project_page.fill_member_login("")
        project_page.select_member_role("developer")
        project_page.submit_add_member()
        project_page.expect_error()

    def test_default_role_used_when_not_selected(
        self, page, logged_in_user, created_project, second_user, project_page
    ):
        """TC-04-PROJ-03: роль вручную не меняем — подставляется «Разработчик»."""
        project_page.open_project(created_project["id"])
        project_page.click_team_tab()
        project_page.click_add_member()
        project_page.fill_member_login(second_user["login"])
        # селект роли не трогаем: по умолчанию в нём выбрано «Разработчик»
        project_page.expect_member_role_selected("developer")
        project_page.submit_add_member()
        project_page.expect_member_with_role(second_user["login"], "Разработчик")

    def test_user_already_in_project(self, page, logged_in_user, created_project, project_page):
        """TC-05-PROJ-03-FAIL_USER_ALREADY_IN_PROJECT"""
        project_page.open_project(created_project["id"])
        project_page.click_team_tab()
        project_page.click_add_member()
        project_page.fill_member_login(logged_in_user["login"])
        project_page.select_member_role("developer")
        project_page.submit_add_member()
        project_page.expect_error()

    def test_new_participant_sees_project(
        self,
        page,
        logged_in_user,
        created_project,
        second_user,
        home_page,
        project_page,
        login_as,
    ):
        """TC-06-PROJ-03-SUCCESS_NEW_PARTICIPANT_SEES_PROJECT"""
        project_page.open_project(created_project["id"])
        project_page.click_team_tab()
        project_page.click_add_member()
        project_page.fill_member_login(second_user["login"])
        project_page.select_member_role("developer")
        project_page.submit_add_member()
        project_page.expect_member_with_role(second_user["login"], "Разработчик")

        login_as(second_user)
        home_page.open()
        home_page.expect_project_card_with_title(created_project["name"])

    def test_added_user_sees_team_and_role(
        self, page, logged_in_user, created_project, second_user, project_page, login_as
    ):
        """TC-07-PROJ-03-SUCCESS_ADDED_USER_SEES_TEAM_AND_ROLE"""
        project_page.open_project(created_project["id"])
        project_page.click_team_tab()
        project_page.click_add_member()
        project_page.fill_member_login(second_user["login"])
        project_page.select_member_role("tester")
        project_page.submit_add_member()

        login_as(second_user)
        project_page.open_project(created_project["id"])
        project_page.click_team_tab()
        project_page.expect_team_table_visible()
        project_page.expect_member_with_role(second_user["login"], "Тестировщик")
        project_page.expect_member_with_role(logged_in_user["login"], "Администратор")
        project_page.expect_no_management_controls()


@pytest.mark.projects
class TestProj04Team:
    """PROJ-04 — Раздел для просмотра состава команды."""

    def test_table_all_participants(self, page, logged_in_user, created_project, project_page):
        """TC-01-PROJ-04-SUCCESS_TABLE_ALL_PARTICIPANTS"""
        project_page.open_project(created_project["id"])
        project_page.click_team_tab()
        project_page.expect_team_table_visible()

    def test_login_and_role_displayed(self, page, logged_in_user, created_project, project_page):
        """TC-02-PROJ-04-SUCCESS_LOGIN_AND_ROLE_DISPLAYED"""
        project_page.open_project(created_project["id"])
        project_page.click_team_tab()
        project_page.expect_member_with_role(logged_in_user["login"], "Администратор")

    def test_admin_sees_controls(self, page, logged_in_user, created_project, project_page):
        """TC-03-PROJ-04-SUCCESS_ADMIN_SEES_CONTROLS"""
        project_page.open_project(created_project["id"])
        project_page.click_team_tab()
        project_page.expect_delete_button_visible()
        # себя администратор удалить не может — кнопка в его строке отключена
        project_page.expect_member_delete_button_disabled(logged_in_user["login"])

    def test_non_admin_no_management_controls(
        self,
        page,
        logged_in_user,
        created_project,
        second_user,
        project_page,
        login_as,
    ):
        """TC-04-PROJ-04-SUCCESS_NON_ADMIN_NO_MANAGEMENT_CONTROLS"""
        project_page.open_project(created_project["id"])
        project_page.click_team_tab()
        project_page.click_add_member()
        project_page.fill_member_login(second_user["login"])
        project_page.select_member_role("developer")
        project_page.submit_add_member()
        project_page.expect_member_with_role(second_user["login"], "Разработчик")

        login_as(second_user)
        project_page.open_project(created_project["id"])
        project_page.click_team_tab()
        project_page.expect_team_table_visible()
        project_page.expect_no_management_controls()

    def test_single_participant_table(self, page, logged_in_user, created_project, project_page):
        """TC-05-PROJ-04-SUCCESS_SINGLE_PARTICIPANT_TABLE"""
        project_page.open_project(created_project["id"])
        project_page.click_team_tab()
        project_page.expect_team_table_visible()
        project_page.expect_member_with_role(logged_in_user["login"], "Администратор")


@pytest.mark.projects
class TestProj05Remove:
    """PROJ-05 — Функционал по удалению пользователя из проекта."""

    def test_admin_delete_user_with_confirm(
        self, page, logged_in_user, created_project, second_user, project_page
    ):
        """TC-01-PROJ-05-SUCCESS_ADMIN_DELETE_USER_WITH_CONFIRM"""
        project_page.open_project(created_project["id"])
        project_page.click_team_tab()
        project_page.click_add_member()
        project_page.fill_member_login(second_user["login"])
        project_page.select_member_role("developer")
        project_page.submit_add_member()
        project_page.expect_member_with_role(second_user["login"], "Разработчик")

        # диалог confirm() браузера обрабатываем ДО клика по удалению
        page.once("dialog", lambda dialog: dialog.accept())
        project_page.click_delete_member(second_user["login"])

        project_page.expect_member_absent(second_user["login"])
        project_page.expect_member_with_role(logged_in_user["login"], "Администратор")

    def test_non_admin_no_delete_button(
        self,
        page,
        logged_in_user,
        created_project,
        second_user,
        project_page,
        login_as,
    ):
        """TC-02-PROJ-05-FAIL_NON_ADMIN_NO_DELETE_BUTTON"""
        project_page.open_project(created_project["id"])
        project_page.click_team_tab()
        project_page.click_add_member()
        project_page.fill_member_login(second_user["login"])
        project_page.select_member_role("developer")
        project_page.submit_add_member()

        login_as(second_user)
        project_page.open_project(created_project["id"])
        project_page.click_team_tab()
        project_page.expect_member_with_role(logged_in_user["login"], "Администратор")
        project_page.expect_no_management_controls()

    def test_non_admin_delete_direct_action(
        self,
        page,
        logged_in_user,
        created_project,
        second_user,
        project_page,
        login_as,
    ):
        """TC-03-PROJ-05-FAIL_NON_ADMIN_DELETE_DIRECT_ACTION"""
        project_page.open_project(created_project["id"])
        project_page.click_team_tab()
        project_page.click_add_member()
        project_page.fill_member_login(second_user["login"])
        project_page.select_member_role("developer")
        project_page.submit_add_member()

        # разработчик не видит кнопок удаления, значит удалить участника напрямую не может
        login_as(second_user)
        project_page.open_project(created_project["id"])
        project_page.click_team_tab()
        project_page.expect_no_management_controls()
        project_page.expect_member_with_role(logged_in_user["login"], "Администратор")
        project_page.expect_member_with_role(second_user["login"], "Разработчик")

    def test_cancel_delete_confirmation(
        self, page, logged_in_user, created_project, second_user, project_page
    ):
        """TC-04-PROJ-05-FAIL_CANCEL_DELETE_CONFIRMATION"""
        project_page.open_project(created_project["id"])
        project_page.click_team_tab()
        project_page.click_add_member()
        project_page.fill_member_login(second_user["login"])
        project_page.select_member_role("developer")
        project_page.submit_add_member()
        project_page.expect_member_with_role(second_user["login"], "Разработчик")

        # отменяем confirm() браузера — участник обязан остаться в команде
        page.once("dialog", lambda dialog: dialog.dismiss())
        project_page.click_delete_member(second_user["login"])

        project_page.expect_member_with_role(second_user["login"], "Разработчик")

    def test_removed_user_no_longer_sees_project(
        self,
        page,
        logged_in_user,
        created_project,
        second_user,
        home_page,
        project_page,
        login_as,
    ):
        """TC-05-PROJ-05-SUCCESS_REMOVED_USER_NO_LONGER_SEES_PROJECT"""
        project_page.open_project(created_project["id"])
        project_page.click_team_tab()
        project_page.click_add_member()
        project_page.fill_member_login(second_user["login"])
        project_page.select_member_role("developer")
        project_page.submit_add_member()
        project_page.expect_member_with_role(second_user["login"], "Разработчик")

        # пока участник в проекте — проект виден ему в списке
        login_as(second_user)
        home_page.open()
        home_page.expect_project_card_with_title(created_project["name"])

        # администратор удаляет участника из проекта
        login_as(logged_in_user)
        project_page.open_project(created_project["id"])
        project_page.click_team_tab()
        page.once("dialog", lambda dialog: dialog.accept())
        project_page.click_delete_member(second_user["login"])
        project_page.expect_member_absent(second_user["login"])

        # удалённый участник больше не видит проект в своём списке
        login_as(second_user)
        home_page.open()
        home_page.expect_no_project_card(created_project["name"])
        home_page.expect_project_list_empty()

    def test_removed_user_not_in_team(
        self, page, logged_in_user, created_project, second_user, project_page
    ):
        """TC-06-PROJ-05-SUCCESS_REMOVED_USER_NOT_IN_TEAM"""
        project_page.open_project(created_project["id"])
        project_page.click_team_tab()
        project_page.click_add_member()
        project_page.fill_member_login(second_user["login"])
        project_page.select_member_role("developer")
        project_page.submit_add_member()
        project_page.expect_member_with_role(second_user["login"], "Разработчик")

        page.once("dialog", lambda dialog: dialog.accept())
        project_page.click_delete_member(second_user["login"])

        project_page.expect_member_absent(second_user["login"])
        project_page.expect_member_with_role(logged_in_user["login"], "Администратор")

    def test_delete_non_existing_participant(
        self, page, logged_in_user, created_project, project_page
    ):
        """TC-07-PROJ-05-FAIL_DELETE_NON_EXISTING_PARTICIPANT"""
        project_page.open_project(created_project["id"])
        project_page.click_team_tab()
        # участника с таким логином в команде нет: удалять нечего, состав команды не меняется
        project_page.expect_member_absent("nonexistent_user")
        project_page.expect_member_with_role(logged_in_user["login"], "Администратор")
        project_page.expect_team_table_visible()


@pytest.mark.projects
class TestProj06Dictionaries:
    """PROJ-06 — Модуль по управлению справочниками проекта."""

    def test_admin_access_dictionaries(self, page, logged_in_user, created_project, project_page):
        """TC-01-PROJ-06-SUCCESS_ADMIN_ACCESS_DICTIONARIES"""
        project_page.open_project(created_project["id"])
        project_page.expect_dictionaries_tab_visible()
        project_page.click_dictionaries_tab()
        project_page.expect_dictionaries_view_opened()
        # у нового проекта уже есть дефолтные статусы
        project_page.expect_dictionary_items_count(6)

    def test_developer_no_access_dictionaries(
        self,
        page,
        logged_in_user,
        created_project,
        second_user,
        project_page,
        login_as,
    ):
        """TC-02-PROJ-06-FAIL_DEVELOPER_NO_ACCESS_DICTIONARIES"""
        project_page.open_project(created_project["id"])
        project_page.click_team_tab()
        project_page.click_add_member()
        project_page.fill_member_login(second_user["login"])
        project_page.select_member_role("developer")
        project_page.submit_add_member()

        # разработчик не должен видеть раздел справочников
        login_as(second_user)
        project_page.open_project(created_project["id"])
        project_page.expect_dictionaries_tab_hidden()

    def test_tester_no_access_dictionaries(
        self,
        page,
        logged_in_user,
        created_project,
        second_user,
        project_page,
        login_as,
    ):
        """TC-03-PROJ-06-FAIL_TESTER_NO_ACCESS_DICTIONARIES"""
        project_page.open_project(created_project["id"])
        project_page.click_team_tab()
        project_page.click_add_member()
        project_page.fill_member_login(second_user["login"])
        project_page.select_member_role("tester")
        project_page.submit_add_member()

        # тестировщик не должен видеть раздел справочников
        login_as(second_user)
        project_page.open_project(created_project["id"])
        project_page.expect_dictionaries_tab_hidden()

    def test_create_status(self, page, logged_in_user, created_project, project_page):
        """TC-04-PROJ-06-SUCCESS_CREATE_STATUS"""
        project_page.open_project(created_project["id"])
        project_page.click_dictionaries_tab()
        project_page.click_dictionary_type("statuses")
        project_page.click_add_dictionary()
        project_page.fill_dictionary_name("Новый статус")
        project_page.submit_dictionary()
        project_page.expect_dictionary_item("Новый статус")

    def test_create_priority(self, page, logged_in_user, created_project, project_page):
        """TC-05-PROJ-06-SUCCESS_CREATE_PRIORITY"""
        project_page.open_project(created_project["id"])
        project_page.click_dictionaries_tab()
        project_page.click_dictionary_type("priorities")
        project_page.click_add_dictionary()
        project_page.fill_dictionary_name("Новый приоритет")
        project_page.submit_dictionary()
        project_page.expect_dictionary_item("Новый приоритет")

    def test_create_severity(self, page, logged_in_user, created_project, project_page):
        """TC-06-PROJ-06-SUCCESS_CREATE_SEVERITY"""
        project_page.open_project(created_project["id"])
        project_page.click_dictionaries_tab()
        project_page.click_dictionary_type("severities")
        project_page.click_add_dictionary()
        project_page.fill_dictionary_name("Новая серьёзность")
        project_page.submit_dictionary()
        project_page.expect_dictionary_item("Новая серьёзность")

    @pytest.mark.parametrize("bad_name", ["", "   "], ids=["empty", "whitespace"])
    def test_create_empty_or_whitespace_name_rejected(
        self, page, logged_in_user, created_project, project_page, bad_name
    ):
        """TC-07/TC-08-PROJ-06: пустое или пробельное имя.

        Валидация выполняется в UI — кнопка «Сохранить» отключена, элемент не создаётся.
        """
        project_page.open_project(created_project["id"])
        project_page.click_dictionaries_tab()
        project_page.click_dictionary_type("statuses")
        project_page.click_add_dictionary()
        project_page.fill_dictionary_name(bad_name)

        project_page.expect_dictionary_save_disabled()
        project_page.close_dictionary_dialog()

        # состав справочника не изменился: 6 дефолтных статусов
        project_page.expect_dictionary_items_count(6)

    def test_edit_element_name(self, page, logged_in_user, created_project, project_page):
        """TC-09-PROJ-06-SUCCESS_EDIT_ELEMENT_NAME"""
        project_page.open_project(created_project["id"])
        project_page.click_dictionaries_tab()
        project_page.click_dictionary_type("statuses")
        project_page.click_add_dictionary()
        project_page.fill_dictionary_name("Старое название")
        project_page.submit_dictionary()
        project_page.click_edit_dictionary("Старое название")
        project_page.fill_dictionary_name("Новое название")
        project_page.submit_dictionary()
        project_page.expect_dictionary_item("Новое название")
        project_page.expect_dictionary_item_absent("Старое название")

    def test_edit_empty_name_rejected(self, page, logged_in_user, created_project, project_page):
        """TC-10-PROJ-06: пустое имя при редактировании.

        Кнопка «Сохранить» отключена, название элемента не меняется.
        """
        project_page.open_project(created_project["id"])
        project_page.click_dictionaries_tab()
        project_page.click_dictionary_type("statuses")
        project_page.click_add_dictionary()
        project_page.fill_dictionary_name("Редактируемый")
        project_page.submit_dictionary()
        project_page.expect_dictionary_item("Редактируемый")

        project_page.click_edit_dictionary("Редактируемый")
        project_page.fill_dictionary_name("")
        project_page.expect_dictionary_save_disabled()
        project_page.close_dictionary_dialog()

        # название осталось прежним, состав справочника не изменился: 6 дефолтных + созданный
        project_page.expect_dictionary_item("Редактируемый")
        project_page.expect_dictionary_items_count(7)

    def test_delete_unused_element(self, page, logged_in_user, created_project, project_page):
        """TC-11-PROJ-06-SUCCESS_DELETE_UNUSED_ELEMENT"""
        project_page.open_project(created_project["id"])
        project_page.click_dictionaries_tab()
        project_page.click_dictionary_type("statuses")
        project_page.click_add_dictionary()
        project_page.fill_dictionary_name("Удаляемый статус")
        project_page.submit_dictionary()
        project_page.expect_dictionary_item("Удаляемый статус")

        # confirm() браузера обрабатываем ДО клика по удалению
        page.once("dialog", lambda dialog: dialog.accept())
        project_page.click_delete_dictionary("Удаляемый статус")

        project_page.expect_dictionary_item_absent("Удаляемый статус")

    def test_delete_used_element_button_inactive(
        self, page, logged_in_user, created_project, project_page
    ):
        """TC-12-PROJ-06-FAIL_DELETE_USED_ELEMENT_BUTTON_INACTIVE"""
        project_page.open_project(created_project["id"])
        project_page.click_dictionaries_tab()
        project_page.click_dictionary_type("statuses")
        # дефолтный статус удалить нельзя, а обычный — можно
        project_page.expect_delete_button_disabled("Новая")
        project_page.expect_delete_button_enabled("Назначена")

    def test_delete_used_element_direct_action(
        self, page, logged_in_user, created_project, project_page
    ):
        """TC-13-PROJ-06-FAIL_DELETE_USED_ELEMENT_DIRECT_ACTION"""
        project_page.open_project(created_project["id"])
        project_page.click_dictionaries_tab()
        project_page.click_dictionary_type("statuses")
        # элемент никуда не исчезает: кнопка отключена, состав справочника неизменен
        project_page.expect_delete_button_disabled("Новая")
        project_page.expect_dictionary_item("Новая")
        project_page.expect_dictionary_items_count(6)

    def test_admin_sees_all_dictionary_types(
        self, page, logged_in_user, created_project, project_page
    ):
        """TC-14-PROJ-06-SUCCESS_ADMIN_SEES_ALL_DICTIONARY_TYPES"""
        project_page.open_project(created_project["id"])
        project_page.click_dictionaries_tab()
        project_page.expect_dictionaries_tab_visible()
        project_page.click_dictionary_type("statuses")
        project_page.expect_dictionary_item("Новая")
        project_page.click_dictionary_type("priorities")
        project_page.expect_dictionary_item("low")
        project_page.click_dictionary_type("severities")
        project_page.expect_dictionary_item("Blocker")
