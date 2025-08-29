from isphere_exceptions.source import SourceOperationFailure
from isphere_exceptions.worker import InternalWorkerError
from worker_classes.thread.timing import timing

from src.browser.samsung_browser import SamsungBrowser
from src.interfaces.session_maker import AbstractSessionMaker
from src.utils import informer


class SessionMakerCommon(AbstractSessionMaker):
    """Генерирует сессию"""

    start_url: str
    session_url: str

    search_button: tuple[str, str]
    search_button_disabled: tuple[str, str]
    main_screen_definition: tuple[str, str]
    result_screen_definition: tuple[str, str]

    def __init__(self) -> None:
        """Конструктор класса"""
        self.browser: SamsungBrowser | None = None
        self.is_prepared = False

    @informer(1, "Preparing the browser")
    async def prepare(self) -> "SessionMakerCommon":
        """Выполняет подготовку браузера для получения сессии.

        :return: SessionMaker
        """
        if not self.browser:
            self.browser = SamsungBrowser(headless=True)
            await self.browser.start_browser()
        self.browser.clean_all_cookies()
        self.is_prepared = True
        return self

    @timing("All the time spent creating the session")
    async def make(self, search_data: str | dict) -> dict:
        """Генерирует сессию

        :param search_data: Данные для поиска (заведомо не существующий аккаунт)
        :return: Сессия
        """
        if not self.is_prepared:
            await self.prepare()
        await self._load_main_page_and_check()
        await self._set_search_data(search_data)
        await self._wait_and_click_search()
        await self._wait_and_check_result()
        self.is_prepared = False
        return self._get_session_request()

    @informer(2, "Loading the main page")
    async def _load_main_page_and_check(self) -> None:
        """Загружает главную страницу и проверяет, что она была загружена корректно.
        Возбуждает исключение SourceOperationFailure в случае ошибки.

        :return: None
        """
        if not self.browser:
            raise InternalWorkerError(
                'Browser not defined in function "_load_main_page_and_check"'
            )
        self.browser.get(self.start_url)
        self.browser.load_page_waiting(self.main_screen_definition)
        if not self.browser.get_element(*self.main_screen_definition):
            raise SourceOperationFailure("Failed to load main page")

    async def _set_search_data(self, data: str | dict) -> None:
        """Устанавливает данные для поиска

        :param data: Данные для поиска (заведомо не существующий аккаунт)
        :return: None
        """
        raise NotImplementedError

    @informer(4, "Waiting for the search button")
    async def _wait_and_click_search(self) -> None:
        """Ожидает, когда кнопка поиска станет доступной и затем кликает по ней.

        :return: None
        """
        if not self.browser:
            raise InternalWorkerError(
                'Browser not defined in function "_wait_and_click_search"'
            )
        self.browser.wait_recaptcha_to_load()
        self.browser.wait_hidden_element_and_click(
            hidden=self.search_button_disabled, clicked=self.search_button
        )

    @informer(5, "Waiting for search result")
    async def _wait_and_check_result(self) -> None:
        """Ожидает результат поиска и проверяет его на корректность.
        Возбуждает исключение SourceOperationFailure в случае ошибки.
        """
        if not self.browser:
            raise InternalWorkerError(
                'Browser not defined in function "_wait_and_check_result"'
            )
        self.browser.result_waiting(self.result_screen_definition)
        if not self.browser.get_element(*self.result_screen_definition):
            raise SourceOperationFailure("Failed to load search result")

    def _get_session_request(self) -> dict:
        """Возвращает запрос браузера для формирования сессии.

        :return: Запрос браузера, на основе которого возможно сформировать сессию.
        """
        if not self.browser or not self.browser.driver:
            raise InternalWorkerError(
                'Browser or driver not defined in function "_get_all_requests"'
            )
        session_request = dict()
        proxy_id = self.browser.get_proxy_id()

        # Если за отведенное время сессия не найдена, возбуждает исключение
        # "Timed out after 5s waiting for request matching ..."
        # (данное исключение обрабатывается при использовании класса AbstractSessionMaker)
        request = self.browser.driver.wait_for_request(self.session_url, timeout=5)

        session_request["request_url"] = request.url
        session_request["request_headers"] = dict(request.headers)
        session_request["response_status_code"] = request.response.status_code
        session_request["proxy_id"] = proxy_id

        return session_request
