import time

from selenium.common import TimeoutException
from selenium.webdriver import ActionChains
from selenium.webdriver.common.by import By
from selenium.webdriver.remote.webelement import WebElement
from selenium.webdriver.support.wait import WebDriverWait

from src.browser.browser import FirefoxBrowser
from src.config.app import ConfigApp
from src.config.settings import IMPLICITLY_WAIT
from src.logger import logging
from src.logic.repository.screen import Screen
from src.logic.repository.screen_names import ScreenNames
from src.logic.repository.screen_repository import ScreenRepository
from src.utils.utils import elapsed_time


class ExtendedBrowser(FirefoxBrowser):
    # Минимальное количество шагов, которые необходимо выполнить при перемещении слайдера капчи.
    COUNT_MIN_STEPS = 20

    def waiting_and_get_screen(
        self, time: int, screen_name: ScreenNames, screen_repository: ScreenRepository
    ) -> Screen | None:
        """Ожидает появление экрана.
        В качестве аргумента screen_name принимает название ожидаемого экрана.
        Извлекает все варианты верстки экрана, которые представлены экземплярами класса Screen,
        из хранилища экранов screen_repository. Далее за отведенное время ConfigApp.SCREEN_WAITING
        десять раз проходит по всем извлеченным версткам (Screen) и проверяет на совпадение с текущим экраном.
        При наличии совпадения, немедленно возвращает найденный экран Screen, по истечении времени
        ConfigApp.SCREEN_WAITING выводит предупреждение в логи и возвращает None.

        :param time: Максимальное время ожидания экрана в секундах.
        :param screen_name: Название ожидаемого экрана.
        :param screen_repository: Репозиторий, который содержит все известные экраны.
        :return: Найденный экран илм None.
        """
        screens = screen_repository.get_screen(screen_name)
        total_definitions = screen_repository.get_screen_definitions(screen_name)

        waiting_by_step = round(time / total_definitions / ConfigApp.MULTIPLIER, 3)

        self.driver.implicitly_wait(0)
        try:
            for _ in range(ConfigApp.MULTIPLIER):
                if screen := self._iterate_screens(screen_name, screens, waiting_by_step):
                    return screen
        finally:
            self.driver.implicitly_wait(IMPLICITLY_WAIT)
        logging.warning(f'Timeout waiting screen "{screen_name}"')
        return None

    def _iterate_screens(
        self, screen_name: ScreenNames, screens: list[Screen], timeout: float
    ) -> Screen | None:
        """Проверяет на совпадение экрана из переданного списка с текущим экраном.
        При наличии совпадения, возвращает найденный экран, иначе None.

        :param screen_name: Название ожидаемого экрана.
        :param screens: Список экранов (различные верстки данного экрана).
        :param timeout: Максимальное время ожидания экрана.
        :return: Найденный экран, или None.
        """
        for number, screen in enumerate(screens):
            for screen_definition in screen.definitions:
                try:
                    self._waiting(screen_definition, timeout)
                    logging.info(
                        f'Founded screen "{screen_name.capitalize()} {number + 1}"'
                    )
                    return screen
                except TimeoutException:
                    pass
        return None

    def waiting_and_get_screens(
        self,
        time: int,
        screen_names: list[ScreenNames],
        screen_repository: ScreenRepository,
    ) -> tuple[ScreenNames, Screen] | tuple[None, None]:
        """Ожидает появление экрана.
        В качестве аргумента screen_names принимает список с названиями ожидаемых экранов.
        Извлекает все варианты верстки экранов, которые представлены экземплярами класса Screen,
        из хранилища экранов screen_repository. Далее за отведенное время ConfigApp.SCREEN_WAITING
        десять раз проходит по всем экранам и их извлеченным версткам (Screen) и проверяет на совпадение
        с текущим экраном. При наличии совпадения, немедленно возвращает найденный экран Screen,
        по истечении времени ConfigApp.SCREEN_WAITING выводит предупреждение в логи и возвращает None.

        :param time: Максимальное время ожидания экрана в секундах.
        :param screen_names: Название ожидаемого экрана.
        :param screen_repository: Репозиторий, который содержит все известные экраны.
        :return: Имя найденного экрана и экран илм None.
        """
        screens = screen_repository.get_screens(screen_names)
        total_definitions = screen_repository.get_screens_definitions(screen_names)

        waiting_by_step = round(time / total_definitions / ConfigApp.MULTIPLIER, 3)

        self.driver.implicitly_wait(0)
        try:
            for _ in range(ConfigApp.MULTIPLIER):
                for name, screen in screens:
                    if scr := self._iterate_screens(name, screen, waiting_by_step):
                        return name, scr
        finally:
            self.driver.implicitly_wait(IMPLICITLY_WAIT)
        logging.warning(f'Timeout waiting screens "{screen_names}"')
        return None, None

    def _waiting(self, target_element: tuple[By, str], timeout: float) -> None:
        """Ожидает появление элемента на странице.
        По истечении времени ожидания возбуждает исключение TimeoutException

        :param target_element: Элемент, который ожидаем.
        :param timeout: Максимальное время ожидания.
        :return: None
        """
        WebDriverWait(self.driver, timeout, poll_frequency=0.05).until(
            lambda res: self.driver.find_element(*target_element)
        )

    def waiting_element_becomes_unavailable(
        self, target_element: tuple[By, str], timeout: float
    ) -> bool:
        """Ожидает пока элемент станет недоступным на странице. Может быть использован для
        проверки изменений на странице в ходе выполнения JavaScript кода (Например, изменилось
        свойство кнопки и она стала доступной для клика, при этом в качестве аргумента передаем
        определение недоступной для клика кнопки).

        :param target_element: Элемент который должен измениться.
        :param timeout: Максимальное время ожидания.
        :return:
        """
        try:
            WebDriverWait(self.driver, timeout, poll_frequency=0.1).until_not(
                lambda res: self.driver.find_element(*target_element)
            )
            return True
        except TimeoutException:
            logging.warning(f"Timeout waiting {target_element}")
            return False

    def moving_slider(self, slider: WebElement, distance: int) -> None:
        """Перемещает по оси Y слайдер на указанное расстояние.
        Слайдер будет перемещен в несколько шагов, не менее 20,
        это необходимо для обхода защиты капчи от решения роботами.

        :param slider: Слайдер, который необходимо переместить.
        :param distance: Расстояние перемещения в пикселях.
        :return: None
        """
        start = time.time()

        step = distance // self.COUNT_MIN_STEPS
        if step == 0:
            step = 1
        fast_move = distance // step
        slow_move = distance - fast_move * step

        logging.info(f"Slider step {step} pixels")

        actions = ActionChains(self.driver)
        actions.click_and_hold(slider).perform()

        for _ in range(fast_move):
            y_offset = 2
            actions.move_by_offset(step, y_offset).perform()
            if y_offset < 8:
                y_offset += 1

        for _ in range(slow_move):
            actions.move_by_offset(1, 0).perform()

        actions.release(slider).perform()

        logging.info(f"Slider movement time {elapsed_time(start)} sec")

    def get_payload(self, screen: Screen) -> str | None:
        """Извлекает информацию из экрана.

        :param screen: Экран из которого необходимо извлечь информацию.
        :return: Найденная информация или None.
        """
        if element := self.get_element(*screen.payloads[0]):
            return element.text
        logging.warning(f"Element not found {element}")
        return None
