import os
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

from pages.base_page import BasePage
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
def playwright_instance() -> Playwright:
    with sync_playwright() as p:
        yield p


@pytest.fixture(scope="session")
def browser(playwright_instance: Playwright) -> Browser:
    browser = playwright_instance.chromium.launch(headless=False)
    yield browser
    browser.close()


@pytest.fixture
def context(browser: Browser) -> BrowserContext:
    context = browser.new_context(
        base_url=BASE_URL,
        viewport={"width": 1280, "height": 720},
    )
    yield context
    context.close()


@pytest.fixture
def page(context: BrowserContext) -> Page:
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
def second_user(unique_suffix: str) -> dict[str, str]:
    return {
        "login": f"second_{unique_suffix}",
        "email": f"second_{unique_suffix}@example.com",
        "password": "Password123!",
        "password_confirmation": "Password123!",
    }


@pytest.fixture
def registered_user(register_page: RegisterPage, test_user: dict[str, str]) -> dict[str, str]:
    register_page.open()
    register_page.fill_login(test_user["login"])
    register_page.fill_email(test_user["email"])
    register_page.fill_password(test_user["password"])
    register_page.fill_confirm_password(test_user["password_confirmation"])
    register_page.submit()
    register_page.expect_redirect_to_home();
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
def created_project(
    home_page: HomePage, logged_in_user: dict[str, str]
) -> dict[str, str]:
    home_page.click_create_project()
    home_page.fill_project_name("Test Project")
    home_page.fill_project_description("Test description")
    home_page.submit_create_project()
    page.wait_for_url("**/projects/*")
    return {"name": "Test Project", "description": "Test description"}


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