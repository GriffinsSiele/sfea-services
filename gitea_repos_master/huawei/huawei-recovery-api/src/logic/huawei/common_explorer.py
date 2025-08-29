"""
Выполняет проверку наличия аккаунты на сайте huawei.

Сайт имеет два варианта верстки главной страницы и страницы с результатами поиска,
три варианта верстки формы решения капчи.

После отправки решения капчи,
если капча решена не верно, на форме капчи выводится ошибка,
если карча решена верно, форма капчи скрывается, сайт переходит на страницу восстановления аккаунта.
"""

from typing import Type

from isphere_exceptions.source import SourceError

from src.browser.extended_browser import ExtendedBrowser
from src.captcha import SliderCaptchaSolver
from src.config.app import ConfigApp
from src.exceptions.exceptions import SessionCaptchaDecodeWarning
from src.interfaces.abstract_screen_repo_conf import AbstractScreenRepositoryConfigurator
from src.logger import logging
from src.logic.handlers.payload_adapter import PayloadAdapter
from src.logic.huawei.explorer_fields import ExplorerFields
from src.logic.repository.screen_names import ScreenNames
from src.request_params import CaptchaImageGet
from src.utils import informer, several_attempts_async


class CommonExplorer(ExplorerFields):
    screen_repository_maker: Type[AbstractScreenRepositoryConfigurator]
    captcha_solver_cls = SliderCaptchaSolver
    captcha_image_getter_cls = CaptchaImageGet
    payload_adapter_cls = PayloadAdapter

    MAIN_PAGE_URL: str
    TARGET_SCREEN_WIDTH: int
    GAP: int

    def __init__(self, browser: ExtendedBrowser):
        super().__init__()
        self.browser = browser
        self.screen_repository = self.screen_repository_maker.make()
        self.is_prepared = False
        self.captcha_solution = 0

    async def prepare(self) -> None:
        if not self.is_prepared:
            await self._load_mail_page()
            self.is_prepared = True

    async def search(self, key: str, data: str) -> dict:
        await self._set_search_data(data)
        background, slider = await self._get_captcha_images()
        await self._solve_captcha(background, slider)
        return await self._parse_response(key, data)

    @informer(step_number=0, step_message="Loading main page")
    @several_attempts_async(3)
    async def _load_mail_page(self) -> None:
        """Загружает главную страницу и проверяет, что она загружена.
        Определяет какая версия страницы загружена и устанавливает ее
        в свойстве класса main_page.

        :return: None
        """
        self.browser.get(url=self.MAIN_PAGE_URL)
        main_page = self.browser.waiting_and_get_screen(
            ConfigApp.WAITING_MAIN, ScreenNames.MAIN, self.screen_repository
        )
        if not main_page:
            raise SourceError("Main page is not loaded")
        self.main_page = main_page

    @informer(step_number=1, step_message="Setting search data")
    async def _set_search_data(self, data: str) -> None:
        self.browser.get_element_and_set_data(*self.main_page.input_fields[0], data)
        # Ждем, пока скрытая кнопка станет недоступной:
        self.browser.waiting_element_becomes_unavailable(self.main_page.buttons[1], 1)
        # Кликаем уже по доступной (не unavailable) кнопке
        self.browser.get_element_and_click(*self.main_page.buttons[0])

    @informer(step_number=2, step_message="Getting captcha images")
    async def _get_captcha_images(self) -> tuple[bytes, bytes]:
        # Ожидаем загрузку формы капчи и определяем загруженную форму
        captcha_page = self.browser.waiting_and_get_screen(
            ConfigApp.WAITING_CAPTCHA, ScreenNames.CAPTCHA, self.screen_repository
        )
        if not captcha_page:
            raise SourceError("Captcha screen is not loaded")
        self.captcha_page = captcha_page

        background_img_url, slider_img_url = self._get_img_urls()
        background_img = await self._download_image(background_img_url)
        front_img = await self._download_image(slider_img_url)
        return background_img, front_img

    def _get_img_urls(self) -> tuple[str, str]:
        background = self.browser.get_element(*self.captcha_page.payloads[0])
        slider = self.browser.get_element(*self.captcha_page.payloads[1])
        if not background or not slider:
            raise SourceError(
                f"Not found captcha web elements {self.captcha_page.payloads[0]} "
                f"or {self.captcha_page.payloads[1]}"
            )

        background_img_url = background.get_attribute("src")
        slider_img_url = slider.get_attribute("src")
        if not background_img_url or not slider_img_url:
            logging.warning(f'Background image url "{background_img_url}"')
            logging.warning(f'Slider image url "{slider_img_url}"')
            raise SourceError("Captcha images url not loaded")
        return background_img_url, slider_img_url

    async def _download_image(self, url: str) -> bytes:
        response = await self.captcha_image_getter_cls(url).request()
        if response.status_code != 200:
            logging.warning(f"Getting img status code: {response.status_code}")
        return response.content

    @informer(step_number=3, step_message=f"Solving captcha")
    async def _solve_captcha(self, background: bytes, slider: bytes) -> None:
        self.captcha_solution = (
            self.captcha_solver_cls(background, slider)
            .template_match()
            .result(self.TARGET_SCREEN_WIDTH, self.GAP)
        )
        if not self.captcha_solution:
            raise SessionCaptchaDecodeWarning()
        logging.info(f"Captcha solution is {self.captcha_solution}")
        slider_web_element = self.browser.get_element(*self.captcha_page.buttons[0])
        self.browser.moving_slider(slider_web_element, self.captcha_solution)
        self._check_captcha_solution()

    def _check_captcha_solution(self) -> None:
        if not self._check_captcha_slider():
            raise SourceError("Captcha slider is not usable")
        # Проверка, что окно капчи удалено, следовательно, капча принята.
        # Если капча не принята, то окно остается и просто обновляется изображение.
        if not self.browser.waiting_element_becomes_unavailable(
            self.captcha_page.buttons[0], ConfigApp.WAITING_CAPTCHA_CHECK
        ):
            raise SessionCaptchaDecodeWarning("Captcha not accepted")

    def _check_captcha_slider(self) -> bool:
        # перечитываем слайдер после проведенных изменений (перемещения)
        slider_web_element = self.browser.get_loaded_element(
            *self.captcha_page.buttons[0]
        )
        if not slider_web_element:
            return True  # Окно с капчей уже закрыто, капча принята
        slider_styles = slider_web_element.get_attribute("style")
        if not slider_styles:
            logging.warning("Failed to get slider styles")
            return False
        if "left: 0px" in slider_styles:
            logging.warning(f"Failed to move slider, slider styles: {slider_styles}")
            return False
        if "left" not in slider_styles:
            logging.warning(f"Failed to move slider, slider styles: {slider_styles}")
            return False
        return True

    @informer(step_number=4, step_message="Receiving and parsing the search result")
    async def _parse_response(self, key: str, data: str) -> dict:
        raise NotImplementedError()
