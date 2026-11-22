"""AUTH-01 и AUTH-02 — модуль регистрации и авторизации пользователя."""

import pytest


@pytest.mark.auth
class TestAuth01Registration:
    """AUTH-01 — Модуль создания пользователя."""

    def test_very_long_login_others_correct(self, page, register_page):
        """TC-01-AUTH-01-FAIL-VERY_LONG_LOGIN-OTHERS_CORRECT"""
        register_page.open()
        register_page.fill_login("a" * 256)
        register_page.fill_email("valid@example.com")
        register_page.fill_password("Password123!")
        register_page.fill_confirm_password("Password123!")
        register_page.submit()
        register_page.expect_error()

    def test_very_long_password_passwords_differ_others_correct(self, page, register_page):
        """TC-02-AUTH-01-FAIL-VERY_LONG_PASSWORD-PASSWORDS_DIFFER-OTHERS_CORRECT"""
        register_page.open()
        register_page.fill_login("valid_user")
        register_page.fill_email("valid@example.com")
        register_page.fill_password("a" * 256)
        register_page.fill_confirm_password("DifferentPass123!")
        register_page.submit()
        register_page.expect_error()

    def test_empty_login_others_correct(self, page, register_page):
        """TC-03-AUTH-01-FAIL-EMPTY_LOGIN_OTHERS_CORRECT"""
        register_page.open()
        register_page.fill_login("")
        register_page.fill_email("valid@example.com")
        register_page.fill_password("Password123!")
        register_page.fill_confirm_password("Password123!")
        register_page.submit()
        register_page.expect_still_on_page()

    def test_very_long_email_correct_format_others_correct(self, page, register_page):
        """TC-04-AUTH-01-FAIL-VERY_LONG_EMAIL_CORRECT_FORM-OTHERS_CORRECT"""
        register_page.open()
        register_page.fill_login("valid_user")
        register_page.fill_email("a" * 250 + "@example.com")
        register_page.fill_password("Password123!")
        register_page.fill_confirm_password("Password123!")
        register_page.submit()
        register_page.expect_error()

    def test_incorrect_email_others_correct(self, page, register_page):
        """TC-05-AUTH-01-FAIL-INCORRECT_EMAIL-OTHERS_CORRECT"""
        register_page.open()
        register_page.fill_login("valid_user")
        register_page.fill_email("not-an-email")
        register_page.fill_password("Password123!")
        register_page.fill_confirm_password("Password123!")
        register_page.submit()
        register_page.expect_still_on_page()

    def test_email_exists_others_correct(self, page, registered_user, register_page):
        """TC-06-AUTH-01-FAIL-EMAIL_EXISTS_OTHERS_CORRECT"""
        register_page.open()
        register_page.fill_login("different_user")
        register_page.fill_email(registered_user["email"])
        register_page.fill_password("Password123!")
        register_page.fill_confirm_password("Password123!")
        register_page.submit()
        register_page.expect_error()

    def test_login_exists_others_correct(self, page, registered_user, register_page):
        """TC-07-AUTH-01-FAIL-LOGIN_EXISTS_OTHERS_CORRECT"""
        register_page.open()
        register_page.fill_login(registered_user["login"])
        register_page.fill_email("different@example.com")
        register_page.fill_password("Password123!")
        register_page.fill_confirm_password("Password123!")
        register_page.submit()
        register_page.expect_error()

    def test_all_correct(self, page, register_page, unique_suffix):
        """TC-08-AUTH-01-SUCCESS_ALL_CORRECT"""
        login = f"newuser_{unique_suffix}"
        register_page.open()
        register_page.fill_login(login)
        register_page.fill_email(f"{login}@example.com")
        register_page.fill_password("Password123!")
        register_page.fill_confirm_password("Password123!")
        register_page.submit()
        register_page.expect_no_error()
        register_page.expect_redirect_to_home()


@pytest.mark.auth
class TestAuth02Login:
    """AUTH-02 — Модуль авторизации пользователя."""

    def test_very_long_login_pass_correct(self, page, login_page):
        """TC-01-AUTH-02-FAIL_VERY_LONG_LOGIN-PASS_CORRECT"""
        login_page.open()
        login_page.fill_login("a" * 256)
        login_page.fill_password("Password123!")
        login_page.submit()
        login_page.expect_error()

    def test_very_long_pass_login_correct(self, page, login_page):
        """TC-02-AUTH-02-FAIL_VERY_LONG_PASS-LOGIN_CORRECT"""
        login_page.open()
        login_page.fill_login("valid_user")
        login_page.fill_password("a" * 256)
        login_page.submit()
        login_page.expect_error()

    def test_login_empty_other_correct(self, page, login_page):
        """TC-03-AUTH-02-FAIL-LOGIN_EMPTY-OTHER_CORRECT"""
        login_page.open()
        login_page.fill_login("")
        login_page.fill_password("Password123!")
        login_page.submit()
        login_page.expect_still_on_page()

    def test_password_very_long_login_correct(self, page, login_page):
        """TC-04-AUTH-02-FAIL-PASSWORD_VERY_LONG-LOGIN_CORRECT"""
        login_page.open()
        login_page.fill_login("valid_user")
        login_page.fill_password("a" * 256)
        login_page.submit()
        login_page.expect_error()

    def test_all_empty(self, page, login_page):
        """TC-05-AUTH-02-FAIL-ALL_EMPTY"""
        login_page.open()
        login_page.fill_login("")
        login_page.fill_password("")
        login_page.submit()
        login_page.expect_still_on_page()

    def test_existing_email_incorrect_password(self, page, login_page, registered_user):
        """TC-06-AUTH-02-FAIL-EXISTING_EMAIL_INCORRECT_PASSWORD"""
        login_page.open()
        login_page.fill_login(registered_user["login"])
        login_page.fill_password("WrongPassword123!")
        login_page.submit()
        login_page.expect_error()

    def test_existing_email_empty_password(self, page, login_page, registered_user):
        """TC-07-AUTH-02-FAIL-EXISTING_EMAIL_EMPTY_PASSWORD"""
        login_page.open()
        login_page.fill_login(registered_user["login"])
        login_page.fill_password("")
        login_page.submit()
        login_page.expect_still_on_page()

    def test_all_fields_correct(self, page, login_page, registered_user):
        """TC-08-AUTH-02-ALL_FIELDS_CORRECT"""
        login_page.open()
        login_page.fill_login(registered_user["login"])
        login_page.fill_password(registered_user["password"])
        login_page.submit()
        login_page.expect_no_error()
        login_page.expect_redirect_to_home()
