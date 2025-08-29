"""
Выполняет проверку наличия аккаунты на сайте xiaomi.

Сайт имеет два варианта верстки главной страницы и страницы с результатами поиска,
три варианта верстки формы решения капчи.

После отправки решения капчи,
если капча решена не верно, на форме капчи выводится ошибка,
если карча решена верно, форма капчи скрывается, сайт переходит на страницу восстановления аккаунта.
"""

from isphere_exceptions.session import SessionCaptchaDecodeError
from isphere_exceptions.source import SourceError, SourceIncorrectDataDetected
from isphere_exceptions.success import NoDataEvent

from src.captcha import CaptchaService
from src.config.app import ConfigApp
from src.exceptions.exceptions import SessionCaptchaDecodeWarning
from src.interfaces.abstract_xiaomi_browser import AbstractXiaomiBrowser
from src.logger import logging
from src.logic.handlers.input_data import InputDataHandler
from src.logic.handlers.response_parser import ResponseParser
from src.logic.repository.screens_configurator import ScreensRepositoryConfigurator
from src.utils import informer, several_attempts_async


class XiaomiExplorer:
    captcha_service_cls = CaptchaService
    response_parser_cls = ResponseParser
    input_data_handler_cls = InputDataHandler

    def __init__(self, browser: AbstractXiaomiBrowser):
        self.browser = browser
        self.captcha_task_id: str | None = None
        self.captcha_solution: str = ""
        self.captcha_src: dict = {}
        self.captcha_solution_timestamp: int | None = None
        self.screen_repository = ScreensRepositoryConfigurator.make(browser)
        self.is_prepared = False

    async def prepare(self) -> None:
        if not self.is_prepared:
            await self._load_mail_page()
            self.is_prepared = True

    async def _search(self, data: str, data_type: str) -> dict:
        await self._set_search_data(data)
        captcha_img = await self._get_captcha_image()
        await self._solve_captcha(captcha_img)
        await self._send_captcha_result()
        return await self._parse_response(data, data_type)

    async def search_phone(self, data: str, data_type: str) -> dict:
        if not self.is_prepared:
            await self.prepare()

        country_code, phone_number = self.input_data_handler_cls.parse_phone(data)
        logging.info(f'Parsed phone. Country: "{country_code}". Phone: "{phone_number}"')
        if not country_code or not phone_number:
            raise SourceIncorrectDataDetected("Error parsing phone number")

        if not self.browser.switch_phone_country(self.screen_repository, country_code):
            raise SourceError("Error switching to search by email")

        return await self._search(phone_number, data_type)

    async def search_email(self, data: str, data_type: str) -> dict:
        if not self.is_prepared:
            await self.prepare()
        if not self.browser.switch_to_email(self.screen_repository):
            raise SourceError("Error switching to search by email")
        return await self._search(data, data_type)

    @informer(step_number=0, step_message="Loading main page")
    @several_attempts_async(3)
    async def _load_mail_page(self) -> None:
        """Загружает главную страницу и проверяет, что она загружена.

        :return: None
        """
        self.browser.get(url=ConfigApp.MAIN_PAGE_URL)
        main_page = self.screen_repository.get_page("main_page")
        self.browser.waiting_screen("Main page", main_page)
        if not main_page.is_current_screen():
            raise SourceError("Main page is not loaded")

    @informer(step_number=1, step_message="Setting search data")
    async def _set_search_data(self, data: str) -> None:
        main_page = self.screen_repository.get_page("main_page")
        main_page.set_data_to_input_field_and_press_enter(data)

    @informer(step_number=2, step_message="Getting captcha image")
    async def _get_captcha_image(self) -> bytes:
        captcha_page = self.screen_repository.get_page("captcha_page")
        founded_captcha = self.browser.waiting_screen("Captcha page", captcha_page)
        if not founded_captcha:
            raise SourceError("Captcha image is not loaded")
        logging.info(f'The "{founded_captcha}" founded')
        if captcha_img := captcha_page.get_payload_as_bytes():
            return captcha_img
        raise SourceError("Failed to get captcha image")

    @informer(
        step_number=3,
        step_message=f"Solving captcha image (it can take {ConfigApp.CAPTCHA_TIMEOUT + 5} seconds or less)",
    )
    async def _solve_captcha(self, img: bytes) -> None:
        captcha_response = await self.captcha_service_cls().post_captcha(
            image=img, timeout=ConfigApp.CAPTCHA_TIMEOUT
        )
        if not captcha_response:
            raise SessionCaptchaDecodeWarning(
                "No response received from the captcha service"
            )

        captcha_solution = captcha_response.get("text", "")
        captcha_task_id = captcha_response.get("task_id", "")
        solved_time = captcha_response.get("time", "")

        if not captcha_solution:
            raise SessionCaptchaDecodeWarning(
                "The captcha service response does not contain the captcha solution"
            )

        logging.info(
            f'Captcha solutions is "{captcha_solution} (id={captcha_task_id}), {solved_time} seconds'
        )
        self.captcha_solution = captcha_solution
        self.captcha_task_id = captcha_task_id

    @informer(step_number=4, step_message="Sending the captcha solution")
    async def _send_captcha_result(self) -> None:
        if not self.captcha_solution:
            raise SessionCaptchaDecodeWarning("Captcha solution missing")
        captcha_page = self.screen_repository.get_page("captcha_page")
        captcha_page.set_data_to_input_field_and_press_enter(self.captcha_solution)
        captcha_page.click_button()

    @informer(
        step_number=5,
        step_message="Receiving and parsing the search result, sending captcha solution report",
    )
    async def _parse_response(self, data: str, data_type: str) -> dict:
        screen = self.browser.waiting_search_result(self.screen_repository)
        try:
            if screen:
                logging.info(f'The "{screen}" founded')
                return self.response_parser_cls.parse_founded_screen(
                    data, data_type, screen, self.screen_repository
                )
            return self.response_parser_cls.parse(data, data_type, self.screen_repository)
        except NoDataEvent as e:
            await self.captcha_service_cls().result_report(self.captcha_task_id, True)
            raise e
        except (SessionCaptchaDecodeError, SessionCaptchaDecodeWarning) as e:
            await self.captcha_service_cls().result_report(self.captcha_task_id, False)
            raise e
