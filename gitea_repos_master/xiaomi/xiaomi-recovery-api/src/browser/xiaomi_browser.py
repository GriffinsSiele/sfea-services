from typing import Generator

from isphere_exceptions.worker import InternalWorkerError
from selenium.common import TimeoutException
from selenium.webdriver import ActionChains, Keys
from selenium.webdriver.common.by import By
from selenium.webdriver.remote.webelement import WebElement
from selenium.webdriver.support.wait import WebDriverWait

from src.browser.selenium_browser import SeleniumBrowser
from src.config.app import ConfigApp
from src.config.settings import IMPLICITLY_WAIT
from src.interfaces.abstract_xiaomi_browser import AbstractXiaomiBrowser
from src.logger import logging
from src.logic.repository.screens import Screen
from src.logic.repository.screens_repository import ScreensRepository


class XiaomiBrowser(SeleniumBrowser, AbstractXiaomiBrowser):
    """Содержит методы для работы с сайтом xiaomi"""

    def waiting_screen(self, screen_title: str, screen: Screen) -> str | None:
        """Ожидает появления экрана. Применяется для ожидания загрузки главного экрана и изображения с капчей.
        Верстка главного экрана и верстка формы с капчей выбирается сайтом xiaomi случайно.
        Для отслеживания загрузки экрана или появления формы с изображением, данный метод за отведенное время
        ConfigApp.SCREEN_WAITING десять раз проходит по всем известным версткам и при обнаружении совпадения
        завершает свою работу. При завершении работы возвращает условное имя верстки, которое было обнаружено или None.

        :param screen_title: Название ожидаемого экрана для отображения в логах.
        :param screen: Экземпляр класса Screen, который содержит определения всех известных форм с капчей.
        :return: Найденное условное имя верстки илм None.
        """
        waiting_by_step = round(
            ConfigApp.SCREEN_WAITING
            / len(screen.definitions)
            / ConfigApp.SEARCH_ATTEMPT_MULTIPLIER,
            3,
        )
        captcha_definitions_count = len(screen.definitions)
        self.driver.implicitly_wait(0)
        try:
            for _ in range(ConfigApp.SEARCH_ATTEMPT_MULTIPLIER):
                for i in range(captcha_definitions_count):
                    try:
                        self._waiting(screen.definitions[i], waiting_by_step)
                        return f"{screen_title} {i + 1}"
                    except TimeoutException:
                        pass
        finally:
            self.driver.implicitly_wait(IMPLICITLY_WAIT)
        logging.warning(f'Timeout waiting "{screen_title}"')
        return None

    def waiting_search_result(self, screen_repository: ScreensRepository) -> str | None:
        """Ожидает загрузки результата поиска. Результат поиска может отображаться как на одной из форм с капчей,
        так и на новом экране. Для отслеживания результата данный метод за отведенное время
        ConfigApp.SEARCH_RESULT_WAITING десять раз проходит по всем известным результатам и при обнаружении совпадения
        завершает свою работу, вернув имя экрана с результатом поиска (ошибка решения капчи, пользователь найден,
        пользователь не найден, ...). Или None, если за отведенное время не нашлось совпадений.

        :param screen_repository: Экземпляр класса ScreensRepository, который содержит возможные результаты поиска.
        :return: Имя экрана с результатом поиска или None.
        """
        (
            captcha_not_solved_page,
            captcha_not_solved_page_count,
        ) = self.__get_screen_and_definitions_count(
            "captcha_not_solved_page", screen_repository
        )
        found_page, found_page_count = self.__get_screen_and_definitions_count(
            "found_page", screen_repository
        )
        not_found_page, not_found_page_count = self.__get_screen_and_definitions_count(
            "not_found_page", screen_repository
        )
        waiting_by_step = round(
            ConfigApp.SEARCH_RESULT_WAITING
            / (captcha_not_solved_page_count + found_page_count + not_found_page_count)
            / ConfigApp.SEARCH_ATTEMPT_MULTIPLIER,
            3,
        )
        self.driver.implicitly_wait(0)
        try:
            for it in range(ConfigApp.SEARCH_ATTEMPT_MULTIPLIER):
                if self._is_waited(
                    captcha_not_solved_page.definitions,
                    captcha_not_solved_page_count,
                    waiting_by_step,
                ):
                    return "captcha_not_solved_page"
                if self._is_waited(
                    found_page.definitions, found_page_count, waiting_by_step
                ):
                    return "found_page"
                if self._is_waited(
                    not_found_page.definitions, not_found_page_count, waiting_by_step
                ):
                    return "not_found_page"
        finally:
            self.driver.implicitly_wait(IMPLICITLY_WAIT)
        logging.warning("Timeout waiting search result")
        return None

    def _waiting(self, target_element: tuple[By, str], timeout: float) -> None:
        """Ожидает появление элемента на странице.
        По истечении времени ожидания возбуждает исключение TimeoutException

        :param target_element: Элемент, который ожидаем.
        :param timeout: Предельное время ожидания.
        :return: None
        """
        WebDriverWait(self.driver, timeout, poll_frequency=0.05).until(
            lambda res: self.driver.find_element(*target_element)
        )

    def _safe_waiting(self, target_element: tuple[By, str], timeout: float) -> None:
        """Ожидает появление элемента на странице.
        По истечении времени ожидания логирует предупреждение и завершает свою работу.

        :param target_element: Элемент, который ожидаем.
        :param timeout: Предельное время ожидания.
        :return: None
        """
        try:
            self._waiting(target_element, timeout)
        except TimeoutException:
            logging.warning(f"Timeout waiting {target_element}")

    def _is_waited(
        self,
        pade_definitions: list[tuple[By, str]],
        pade_definitions_count: int,
        waiting_by_step: float,
    ) -> bool:
        """Проходит по всем известным версткам экрана и в случае совпадения возвращает True иначе False.

        :param pade_definitions: Список известных версток экрана.
        :param pade_definitions_count: Количество элементов в pade_definitions.
        :param waiting_by_step: Ожидание на появление одного из экранов.
        :return: Результат поиска совпадений True или False.
        """
        for i in range(pade_definitions_count):
            try:
                self._waiting(pade_definitions[i], waiting_by_step)
                return True
            except TimeoutException:
                pass
        return False

    @staticmethod
    def __get_screen_and_definitions_count(
        screen_name: str, screen_repository: ScreensRepository
    ) -> tuple[Screen, int]:
        """Извлекает из хранилища экран по имени, вычисляет количество его определений (варианты версток данного экрана)
        и возвращает их в качестве результата.

        :param screen_name: Имя экрана для извлечения.
        :param screen_repository: Экземпляр класса ScreensRepository, который содержит все известные экраны.
        :return: Экземпляр класса Screen и количество его определений.
        """
        screen: Screen = screen_repository.get_page(screen_name)
        if not screen:
            raise InternalWorkerError(f'Screen "{screen_name}" not defined')
        definitions_count = len(screen.definitions)
        return screen, definitions_count

    def switch_to_email(self, screen_repository: ScreensRepository) -> bool:
        """Переключает форму поиска на поиск по e-mail. Поле с переключателем реализовано через тэг input,
        при клике на котором всплывает окно с выбором поиска "Номер телефона", "E-mail" и
        "Идентификатор аккаунта Xiaomi", при наведении курсора мыши на одно из них, меняется верстка (отрабатывает
        JavaScript код) после подтверждения выбора, всплывающее окно закрывается (отрабатывает JavaScript код).
        Стандартными средствами selenium find_element(by, selector).send_keys(data) не удается изменить значение поля.
        Данный метод отправляет данные в поле, что провоцирует открытие всплывающего окна с выбором,
        отправляет нажатие клавиш стрелка вниз и Enter, что позволяет переключиться на поиск по E-mail.
        Проверяет, что переключение произошло успешно и возвращает True иначе False.

        :param screen_repository: Экземпляр класса ScreensRepository, который содержит все известные экраны.
        :return: Переключение выполнено успешно (True) или нет (False).
        """
        email_page = screen_repository.get_page("email_page")
        if not email_page or not email_page.input_fields:
            raise InternalWorkerError('Screen "email_page" not defined')
        element = self.get_element_and_set_data(*email_page.input_fields[0], "E-mail")
        self._safe_waiting(email_page.input_fields[1], 0.2)
        element.send_keys(Keys.DOWN)
        self._safe_waiting(email_page.input_fields[2], 0.2)
        element.send_keys(Keys.ENTER)
        self._safe_waiting(email_page.input_fields[3], 0.2)
        if email_page.is_current_screen():
            return True
        return False

    def switch_phone_country(
        self, screen_repository: ScreensRepository, country_code: str
    ) -> bool:
        """Переключает в форме поиска код страны.
        Код страны оформлен отдельным полем, при клике на него появляется дополнительная верстка,
        которая содержит список стран и их коды. Перемещение стрелками клавиатуры не работает, табуляция
        не работает. Данный метод эмулирует клик мыши для отображения списка стран, ожидает пока отработает
        JavaScript и список отобразится (список может быть в двух вариантах верстки, код это учитывает),
        перебирает все строки в поисках нужной страны, затем эмулирует клик мыши на ней,
        что приводит к выбору нужного кода страны.

        :param screen_repository: Экземпляр класса ScreensRepository, который содержит все известные экраны.
        :param country_code: Код страны, которую проверяем.
        :return: Булевый результат, успех или нет.
        """
        if self._is_country_selected(screen_repository, country_code):
            return True

        # кликаем на элементе для открытия окна со странами
        countries_switcher = screen_repository.get_page("countries_switcher")
        self._safe_waiting(countries_switcher.definitions[0], 0.5)
        element = self.get_loaded_element(*countries_switcher.definitions[0])
        ActionChains(self.driver).click(element).perform()

        # ожидаем загрузки окна со странами
        countries_page = screen_repository.get_page("countries_page")
        self.waiting_screen("countries_page", countries_page)

        # находим элемент с нужной страной и кликаем по этому элементу
        element = self._find_country(screen_repository, country_code)
        if element:
            ActionChains(self.driver).click(element).perform()
            return True
        return False

    def _is_country_selected(
        self, screen_repository: ScreensRepository, country_code: str
    ) -> bool:
        """Проверяет выбрана нужная страна (код страны) или нет.

        :param screen_repository: Экземпляр класса ScreensRepository, который содержит все известные экраны.
        :param country_code: Код страны, которую проверяем.
        :return: Булевый результат проверки.
        """
        current_country_selector = screen_repository.get_page("current_country_selector")
        element = self.get_loaded_element(*current_country_selector.definitions[0])
        if not element:
            raise InternalWorkerError("Could not determine current country")
        if country_code == element.text:
            return True
        return False

    def _find_country(
        self, screen_repository: ScreensRepository, country_code: str
    ) -> WebElement | None:
        """Находит и возвращает элемент, содержащий страну, на которую необходимо переключить поиск.

        :param screen_repository: Экземпляр класса ScreensRepository, который содержит все известные экраны.
        :param country_code: Код страны, которую проверяем.
        :return: WebElement.
        """

        def get_template(max_number: int, code: str) -> Generator:
            for number in range(max_number):
                for i in range(2):
                    yield (
                        f"/html/body/div[{3 + i}]/div/div[3]/div/div/div/div/div[2]/div/div[2]/div"
                        f'[{number + 1}]/div[2]/span[contains(text(), "{code}")]'
                    )

        len_country_total = self._get_total_countries(screen_repository)

        for template in get_template(len_country_total, country_code):
            if element := self.get_loaded_element(By.XPATH, template):
                return element

        logging.warning(f'Country code "{country_code}" not found')
        return None

    def _get_total_countries(self, screen_repository: ScreensRepository) -> int:
        """Вычисляет количество доступных для выбора стран. Данное значение используется при переборе
        список доступных стран. Если по каким-то причинам количество определить не удалось,
        используется значение по умолчанию равное 244 страны (текущее значение на сайте).

        :param screen_repository: Экземпляр класса ScreensRepository, который содержит все известные экраны.
        :return: Количество стран.
        """
        countries_page = screen_repository.get_page("countries_page")

        country_page = None
        for definition in countries_page.definitions:
            if country_page := self.get_element(*definition):
                break

        if country_page:
            country_total = country_page.find_elements(By.TAG_NAME, "div")
            len_country_total = len(country_total)

            # Уточняем количество.
            # Тег со страной и ее кодом содержит два дочерних тега, в одном страна, в другом - код.
            # По этой причине для получения количества стран делим на три.
            if len_country_total % 3:
                logging.warning(
                    "Check the calculation of the number of countries, division error"
                )
            return len_country_total // 3

        logging.warning(
            "Check the calculation of the number of countries, countries page error"
        )
        return 244
