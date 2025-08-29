import logging
import pathlib
from collections import deque
from time import sleep
from typing import Any

import pydash
from isphere_exceptions.session import SessionCaptchaDetected
from isphere_exceptions.source import SourceIncorrectDataDetected, SourceOperationFailure
from putils_logic.putils import PUtils
from pydash import get, merge_with
from selenium.common import WebDriverException
from selenium.webdriver.common.by import By

from src.config.app import ConfigApp
from src.config.settings import DEFAULT_FOLDER, MAX_LINKS_ON_SCREEN
from src.exceptions import GoogleSessionCaptchaDecodeError
from src.exceptions.google_exceptions import GoogleSourceOperationFailure
from src.interfaces.abstract_browser import AbstractBrowser
from src.interfaces.abstract_google_captcha_service import AbstractGoogleCaptchaService
from src.interfaces.event import Event
from src.logic.google.connections_auth import (
    CAPTCHA_NOT_SOLVED_SCREENS,
    CAPTCHA_SCREENS_FOR_SOLVING,
    EXTERNAL_SCREEN_NAME,
    MAIN_SIMILAR_SCREENS,
)
from src.logic.google.screen import BaseScreen
from src.logic.google.screen_repository import AbstractScreensRepository
from src.logic.utils import get_domain_url

_current_file_path = pathlib.Path(__file__).parent.absolute()
_root_dir = PUtils.bp(_current_file_path, "..", "..", "..")


class ScreensExplorer:
    main_page_title: str
    main_page_input: str
    main_similar_screens: list = MAIN_SIMILAR_SCREENS
    captcha_not_solved_screens = CAPTCHA_NOT_SOLVED_SCREENS
    captcha_screens_for_solving = CAPTCHA_SCREENS_FOR_SOLVING

    def __init__(
        self,
        start_url: str,
        browser: AbstractBrowser,
        screens_repository: AbstractScreensRepository,
        captcha_service: AbstractGoogleCaptchaService,
    ):
        self.start_url: str = start_url
        self.browser: AbstractBrowser = browser
        self.screens_repository: AbstractScreensRepository = screens_repository
        self.captcha_service = captcha_service
        self.captcha_id: str | None = None
        self.traveled_screens: list[str] = []
        self.browser_prepared: bool = False
        self.new_screen_event = Event()
        self.__current_screen: BaseScreen | None = None
        self.__infinity_cycle_protection: int = len(
            self.screens_repository.get_all_page_titles()
        )
        self.__screens_deque: deque[str] = deque([])
        self.result: dict = {}

    def prepare_browser(self) -> None:
        """Подготавливает браузер к сбору данных (дает выигрыш более 3 секунд)."""
        if not self.browser.is_started:
            self.browser.start_browser()
        self.browser.get(self.start_url)
        self.browser_prepared = True

    def close_browser(self) -> None:
        """Закрывает браузер."""
        self.browser.close_browser()
        self.browser_prepared = False

    async def search(self, payload: Any) -> dict:
        """Запускает поиск.

        :param payload: телефон или email по которому будет осуществляться поиск.
        :return: словарь с результатами поиска.
        """
        payload = get(payload, "phone") or get(payload, "email") or payload

        if not isinstance(payload, str):
            raise SourceIncorrectDataDetected()

        if not self.browser_prepared:
            self.prepare_browser()

        if not self.screens_repository:
            logging.error("Screen repository not found.")
            raise SourceOperationFailure()

        main_screen = self.screens_repository.get_page(self.main_page_title)
        if not main_screen:
            logging.error("Main screen not loaded from repository.")
            raise SourceOperationFailure()

        if not main_screen.is_loaded_screen:
            logging.error("Main screen is not loaded from internet.")
            raise SourceOperationFailure()

        self.__screens_deque = deque(pydash.get(main_screen.follow, "0.screens", []))
        self.traveled_screens = [self.main_page_title]

        infinity_cycle_protection = self.__infinity_cycle_protection

        # вводим данные для поиска, жмем Enter и ждем загрузки страницы
        self._process_the_main_screen(payload)

        while self.__screens_deque and infinity_cycle_protection > 0:
            screen_title = self.__screens_deque.popleft()
            next_screen = self.screens_repository.get_page(screen_title)
            if next_screen and next_screen.is_loaded_screen:
                logging.info(f'Founded screen "{screen_title}"')

                if (
                    self.captcha_id
                    and screen_title not in self.captcha_screens_for_solving
                    and screen_title not in self.captcha_not_solved_screens
                ):
                    self.__report_captcha_solution(True)

                self.__current_screen = next_screen
                self._processing_the_current_screen(screen_title)
                if self.__current_screen.is_end_page:
                    break
                elif next_screens := self.__go_next_screen_and_check_transition(
                    screen_title
                ):
                    self.__screens_deque = deque(next_screens)

            infinity_cycle_protection -= 1

            if self.__screens_deque:
                continue

            if not self._is_google_page():
                break

            # Поиск по всем экранам
            logging.warning("Transition not found. Search started across all screens.")
            if founded_screen := self.__search_for_all_screens():
                screen_title = founded_screen
                self._processing_the_current_screen(screen_title)
                if self.__current_screen and not self.__current_screen.is_end_page:
                    if next_screens := self.__go_next_screen_and_check_transition(
                        screen_title
                    ):
                        self.__screens_deque = deque(next_screens)
                logging.warning(f"Transition detected. Founded screen {screen_title}.")
                continue
            logging.warning("Search across all screens did not produce results.")

            self.traveled_screens.append("!UnknownScreen!")
            logging.warning(f"Path covered: {self.traveled_screens}")
            path_to_save = self.browser.save_state()  # type: ignore
            logging.warning(f'New screen is saved in "{path_to_save}"')

            self.new_screen_event(
                PUtils.bp(_root_dir, DEFAULT_FOLDER, path_to_save), payload
            )

            # "слепые клики"
            logging.info("Trying blind clicks.")
            if founded_screen := self.__blind_clicks():
                screen_title = founded_screen
                self._processing_the_current_screen(screen_title)
                if self.__current_screen and not self.__current_screen.is_end_page:
                    if next_screens := self.__go_next_screen_and_check_transition(
                        screen_title
                    ):
                        self.__screens_deque = deque(next_screens)
                logging.info("Blind clicks helped.")
            else:
                logging.warning("Blind clicks did not produce results.")
                break

        logging.info(f"Full path: {self.traveled_screens}.")
        logging.info(f"Key {payload}, Search results: {self.result}")
        return self.result

    def _is_google_page(self) -> bool:
        current_url = self.browser.get_current_url()
        if current_url.startswith(ConfigApp.GOOGLE_URL):
            return True
        self.__update_result(
            EXTERNAL_SCREEN_NAME,
            {
                "found": True,
                "external_auth": True,
                "external_url": get_domain_url(current_url),
            },
        )
        self.traveled_screens.append(EXTERNAL_SCREEN_NAME)
        return False

    def _process_the_main_screen(self, payload: str) -> None:
        """
        Выполняет обработку главного экрана.
        Заполняет поле input данными поиска, переходит на следующий экран и проверяет переход.
        """
        self.browser.get_element_set_data_and_enter(By.ID, self.main_page_input, payload)

        main_screen = self.screens_repository.get_page(self.main_page_title)
        if not main_screen:
            raise SourceOperationFailure(
                message="Main screen not loaded from repository."
            )
        if not main_screen.is_loaded_screen:
            return None

        if self._check_for_similar_screens(self.main_similar_screens):
            return None

        link = main_screen.follow[0].get("link")
        if not link:
            raise SourceOperationFailure(message="link not loaded from main screen.")

        for attempt in range(ConfigApp.MAX_LEAVE_MAIN_PAGE_ATTEMPTS):
            logging.warning(
                f"Failed to leave main page, attempt {attempt + 1} of {ConfigApp.MAX_LEAVE_MAIN_PAGE_ATTEMPTS}."
            )
            self.browser.get_element_and_click(*link)
            if not main_screen.is_loaded_screen:
                return None
            if self._check_for_similar_screens(self.main_similar_screens):
                return None
            sleep(0.1)

        raise GoogleSourceOperationFailure(message="Failed to leave main page")

    def _check_for_similar_screens(self, similar_screens: list) -> bool:
        """Проверяет экраны, на которых может определиться начальный экран"""
        for similar_screen in similar_screens:
            if screen := self.screens_repository.get_page(similar_screen):
                if screen.is_loaded_screen:
                    self.__screens_deque = deque(
                        [similar_screen]
                    )  # сокращаем поиск, так как экран уже найден
                    return True
        return False

    def _processing_the_current_screen(self, screen_title: str) -> None:
        """Выполняет обработку найденного экрана.

        :param screen_title: название экрана.
        """
        # Проверка на совпадение с первичными свойствами других экранов
        if not self.__current_screen:
            return None

        check_result = self.__current_screen.check_on_other_screen(
            self.screens_repository.get_main_definitions_from_all_pages(
                except_list=(self.main_page_title, screen_title)
            )
        )
        if check_result:
            logging.warning(
                f"Main definition {check_result} "
                f"matches screen {screen_title}, which is not correct."
            )

        self.traveled_screens.append(screen_title)

        if self.__current_screen.has_useful_data:
            try:
                self.__update_result(screen_title, self.__current_screen.extract_data())
            except SessionCaptchaDetected:
                self.captcha_id = self.__solve_captcha_and_set_result()
            except GoogleSessionCaptchaDecodeError as e:
                self.__report_captcha_solution(False)
                raise e

    def __solve_captcha_and_set_result(self) -> str | None:
        """Решает капчу и устанавливает результат в поле input"""
        image_png = None
        total_steps = 3
        for step in range(total_steps):
            image_element = self.browser.get_element(*self.captcha_service.image_tag)
            try:
                image_png = image_element.screenshot_as_png
            except WebDriverException:
                logging.warning(
                    f"Cannot take captcha image screenshot, attempt {step + 1} of {total_steps}"
                )
                sleep(0.1)
                continue
            break

        if not image_png:
            logging.warning(f"Failed to take captcha image screenshot")
            raise GoogleSessionCaptchaDecodeError(
                message="Не удалось сделать снимок экрана с изображением капчи"
            )

        solved_captcha = self.captcha_service.solve_captcha(
            image_png, ConfigApp.CAPTCHA_TIMEOUT
        )

        if not solved_captcha:
            raise GoogleSessionCaptchaDecodeError()

        self.browser.get_element_and_clear(*self.captcha_service.input_tag)
        self.browser.get_element_and_set_data(
            *self.captcha_service.input_tag, solved_captcha.get("text", "")
        )
        return solved_captcha.get("task_id")

    def __report_captcha_solution(self, solution_result: bool) -> None:
        """Отправляет отчет о решении капчи."""
        if self.captcha_id:
            self.captcha_service.solve_report(self.captcha_id, solution_result)
        self.captcha_id = None

    def __go_next_screen_and_check_transition(
        self, screen_title: str
    ) -> list[str] | None:
        """
        Переходит на следующий экран и выполняет проверку, что экран изменился.
        Если это не так - пробует перейти по следующей ссылке экрана и повторяет проверку.

        :param screen_title: название экрана.
        :return: список возможных для перехода экранов.
        """
        if self.traveled_screens.count(screen_title) >= ConfigApp.MAX_ONE_SCREEN_ENTRY:
            raise SourceOperationFailure(
                message="Limit of attempts to go to next screen exceeded"
            )

        if not self.__current_screen or not self.__current_screen.follow:
            return None

        link_position = 0
        link = self.__current_screen.follow[link_position].get("link")
        if not link:
            return None

        self.browser.get_element_and_click(*link)

        if (
            self.__current_screen.is_loaded_screen
            and screen_title not in self.captcha_screens_for_solving
        ):
            infinity_links_cycle_protection = int(MAX_LINKS_ON_SCREEN)
            while (
                self.__current_screen.is_loaded_screen
                and infinity_links_cycle_protection > 0
            ):
                logging.warning(
                    "Failed to follow the link "
                    f"{self.__current_screen.follow[link_position].get('link')} "
                    f"on screen {screen_title}"
                )
                link_position += 1
                try:
                    if next_link := self.__current_screen.follow[link_position]["link"]:
                        self.browser.get_element_and_click(*next_link)
                except (IndexError, KeyError):
                    link_position -= 1
                    break
                infinity_links_cycle_protection -= 1

        return self.__current_screen.follow[link_position].get("screens")

    def __search_for_all_screens(self) -> str | None:
        """Выполняет поиск по всем экранам.

        :return: название найденного экрана или None.
        """
        list_screen_titles = self.screens_repository.get_all_page_titles().copy()
        if self.main_page_title in list_screen_titles:
            list_screen_titles.remove(self.main_page_title)
        for screen_title in list_screen_titles:
            screen = self.screens_repository.get_page(screen_title)
            if screen and screen.is_loaded_screen:
                self.__current_screen = screen
                return screen_title
        return None

    def __blind_clicks(self) -> str | None:
        """Выполняет клики по всем известным ссылкам.
        В случае успеха, запускает поиск по всем экранам,
        если экран найден - возвращает название найденного экрана,
        если экран не найден - продолжает клики по оставшимся ссылкам.

        :return: название найденного экрана или None.
        """
        for link in self.screens_repository.get_all_page_links():
            if self.browser.get_loaded_element(*link):
                self.browser.get_element_and_click(*link)
                if screen_title := self.__search_for_all_screens():
                    return screen_title
        return None

    def __update_result(self, screen_title: str, new_data: dict | None) -> None:
        """Обновляет результат полученными с экрана данными.

        :param new_data: данные для обновления.
        """
        if not new_data:
            return None
        logging.info(f"{screen_title}: {new_data} - data received")

        for key, value in new_data.items():
            if key in self.result and self.result[key] is not None:
                merge_with(
                    self.result,
                    {key: value},
                    lambda result_val, new_data_val: result_val + new_data_val,
                )
            else:
                self.result.update({key: value})
