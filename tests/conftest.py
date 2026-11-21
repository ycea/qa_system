import os
from collections.abc import Callable, Iterator
from datetime import datetime

import pytest
from dotenv import load_dotenv
from playwright.sync_api import (
    Browser,
    BrowserContext,
    Page,
    Playwright,
    sync_playwright,
)

from pages.bugs_page import BugsPage
from pages.dashboard_page import DashboardPage
from pages.home_page import HomePage
from pages.login_page import LoginPage
from pages.metrics_page import MetricsPage
from pages.project_page import ProjectPage
from pages.register_page import RegisterPage
from pages.reports_page import ReportsPage

load_dotenv()

BASE_URL = os.getenv("BASE_URL", "http://localhost:3000")


@pytest.fixture(scope="session")
def playwright_instance() -> Iterator[Playwright]:
    with sync_playwright() as p:
        yield p


@pytest.fixture(scope="session")
def browser(playwright_instance: Playwright) -> Iterator[Browser]:
    browser = playwright_instance.chromium.launch(headless=False)
    yield browser
    browser.close()


@pytest.fixture
def context(browser: Browser) -> Iterator[BrowserContext]:
    context = browser.new_context(
        base_url=BASE_URL,
        viewport={"width": 1280, "height": 720},
    )
    yield context
    context.close()


@pytest.fixture
def page(context: BrowserContext) -> Iterator[Page]:
    page = context.new_page()
    yield page
    page.close()


@pytest.fixture
def unique_suffix() -> str:
    return datetime.now().strftime("%Y%m%d%H%M%S%f")[:-3]


@pytest.fixture
def test_user(unique_suffix: str) -> dict[str, str]:
    return {
        "login": f"user_{unique_suffix}",
        "email": f"user_{unique_suffix}@example.com",
        "password": "Password123!",
        "password_confirmation": "Password123!",
    }


@pytest.fixture
def second_user(browser: Browser, unique_suffix: str) -> dict[str, str]:
    """Второй пользователь — сразу зарегистрированный.

    Регистрация выполняется в отдельном браузерном контексте, чтобы не подменить
    сессию пользователя, под которым работает текущий тест.
    """
    user = {
        "login": f"second_{unique_suffix}",
        "email": f"second_{unique_suffix}@example.com",
        "password": "Password123!",
        "password_confirmation": "Password123!",
    }

    context = browser.new_context(base_url=BASE_URL)
    page = context.new_page()
    try:
        register_page = RegisterPage(page)
        register_page.open()
        register_page.fill_login(user["login"])
        register_page.fill_email(user["email"])
        register_page.fill_password(user["password"])
        register_page.fill_confirm_password(user["password_confirmation"])
        register_page.submit()
        register_page.expect_redirect_to_home()
    finally:
        context.close()

    return user


@pytest.fixture
def registered_user(register_page: RegisterPage, test_user: dict[str, str]) -> dict[str, str]:
    register_page.open()
    register_page.fill_login(test_user["login"])
    register_page.fill_email(test_user["email"])
    register_page.fill_password(test_user["password"])
    register_page.fill_confirm_password(test_user["password_confirmation"])
    register_page.submit()
    register_page.expect_redirect_to_home()
    return test_user


@pytest.fixture
def logged_in_user(login_page: LoginPage, registered_user: dict[str, str]) -> dict[str, str]:
    login_page.open()
    login_page.fill_login(registered_user["login"])
    login_page.fill_password(registered_user["password"])
    login_page.submit()
    login_page.expect_redirect_to_home()
    return registered_user


@pytest.fixture
def login_as(login_page: LoginPage) -> Callable[[dict[str, str]], dict[str, str]]:
    """Вход под другим пользователем в текущем браузерном контексте.

    Backend при логине пересоздаёт сессию (AuthController::login -> session()->regenerate()),
    поэтому повторный вход просто переключает текущего пользователя.
    """

    def _login_as(user: dict[str, str]) -> dict[str, str]:
        login_page.login(user)
        return user

    return _login_as


@pytest.fixture
def created_project(
    page: Page, home_page: HomePage, logged_in_user: dict[str, str]
) -> dict[str, str]:
    name = "Test Project"
    description = "Test description"

    home_page.click_create_project()
    home_page.fill_project_name(name)
    home_page.fill_project_description(description)

    # id созданного проекта берём из ответа API: POST /api/projects -> 201 {"message", "project"}
    with page.expect_response(
        lambda response: (
            response.request.method == "POST" and response.url.endswith("/api/projects")
        )
    ) as response_info:
        home_page.submit_create_project()

    response = response_info.value
    assert response.ok, f"Не удалось создать проект: {response.status} {response.text()}"

    # фронтенд остаётся на списке проектов, поэтому ждём появления карточки, а не смены URL
    home_page.expect_project_card_with_title(name)

    return {
        "id": str(response.json()["project"]["id"]),
        "name": name,
        "description": description,
    }


@pytest.fixture
def login_page(page: Page) -> LoginPage:
    return LoginPage(page)


@pytest.fixture
def register_page(page: Page) -> RegisterPage:
    return RegisterPage(page)


@pytest.fixture
def home_page(page: Page) -> HomePage:
    return HomePage(page)


@pytest.fixture
def project_page(page: Page) -> ProjectPage:
    return ProjectPage(page)


@pytest.fixture
def bugs_page(page: Page) -> BugsPage:
    return BugsPage(page)


@pytest.fixture
def metrics_page(page: Page) -> MetricsPage:
    return MetricsPage(page)


@pytest.fixture
def dashboard_page(page: Page) -> DashboardPage:
    return DashboardPage(page)


@pytest.fixture
def reports_page(page: Page) -> ReportsPage:
    return ReportsPage(page)
