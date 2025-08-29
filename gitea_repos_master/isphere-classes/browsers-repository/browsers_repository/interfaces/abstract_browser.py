from abc import abstractmethod

from selenium.webdriver.common.by import By
from undetected_chromedriver import WebElement

from browsers_repository.interfaces.utils import SingletonABCMeta


class AbstractBrowser(metaclass=SingletonABCMeta):

    @abstractmethod
    def start_browser(self) -> None:
        """Запускает браузер."""
        pass

    @abstractmethod
    def close_browser(self) -> None:
        """Завершает работу web драйвера."""
        pass

    @abstractmethod
    def get(self, url: str) -> bool:
        """Переходит по переданному url-адресу и ждет загрузку страницы
        в течение времени IMPLICITLY_WAIT (указывается в переменных окружения).

        :param url: URL-адрес страницы.
        :return: Результат перехода (True - успех, False - ошибка).
        """
        pass

    @property
    @abstractmethod
    def page_source(self) -> str:
        """Возвращает текущую html-страницу.

        :return: html код страницы в формате str.
        """
        pass

    @abstractmethod
    def get_element(self, by: By, selector: str) -> WebElement | None:
        """Возвращает элемент по указанным параметрам поиска.
        Имеет задержку IMPLICITLY_WAIT (указывается в переменных окружения)
        на ожидание появления элемента. Если элемент не найден - вернет None.

        :param by: Локатор, определяет стратегию поиска.
        :param selector: Ключ поиска.
        :return: WebElement или None.
        """
        pass

    @abstractmethod
    def get_loaded_element(self, by: By, selector: str) -> WebElement | None:
        """Возвращает элемент по указанным параметрам поиска.
        Метод не имеет задержки IMPLICITLY_WAIT (указывается в переменных окружения)
        на ожидание появления элемента, предполагается, что элемент загружен.
        Если элемент не найден - вернет None.

        :param by: Локатор, определяет стратегию поиска.
        :param selector: Ключ поиска.
        :return: WebElement или None.
        """
        pass

    @abstractmethod
    def get_element_and_click(self, by: By, selector: str) -> WebElement | None:
        """Находит элемент на странице и выполняет click по нему.
        Имеет задержку для ожидания обновления страницы.
        Возвращает найденный элемент или None, если элемент не был найден.

        :param by: Локатор, определяет стратегию поиска.
        :param selector: Ключ поиска.
        :return: WebElement или None.
        """
        pass

    def get_element_and_set_data(
        self, by: By, selector: str, data: str
    ) -> WebElement | None:
        """Находит элемент на странице и вводит данные.
        Целевой элемент должен обладать возможностью принимать данные
        (input, area, ...). Возвращает найденный элемент или None,
        если элемент не был найден.

        :param by: Локатор, определяет стратегию поиска.
        :param selector: Ключ поиска.
        :param data: Данные для ввода.
        :return: WebElement или None.
        """
        pass

    @abstractmethod
    def get_element_and_clear(self, by: By, selector: str) -> WebElement | None:
        """Находит элемент на странице и очищает введенные данные
        (элементы типа input, area, ...). Возвращает найденный элемент или None,
        если элемент не был найден.

        :param by: Локатор, определяет стратегию поиска.
        :param selector: Ключ поиска.
        :return: WebElement или None.
        """
        pass

    @abstractmethod
    def get_element_set_data_and_enter(
        self, by: By, selector: str, data: str
    ) -> WebElement | None:
        """Находит элемент на странице, вводит данные и отправляет сигнал нажатия Enter.
        Имеет задержку для ожидания обновления страницы.
        Возвращает найденный элемент или None, если элемент не был найден.

        :param by: Локатор, определяет стратегию поиска.
        :param selector: Ключ поиска.
        :param data: Данные для ввода.
        :return: WebElement или None.
        """
        pass

    @abstractmethod
    def waiting(self, by: By, selector: str, timeout: float) -> None:
        """Ожидает появление элемента на странице.
        По истечении времени ожидания возбуждает исключение TimeoutException.

        :param by: Локатор, определяет стратегию поиска элемента, появление которого ожидаем.
        :param selector: Ключ поиска.
        :param timeout: Максимальное время ожидания.
        :return: None
        """
        pass

    @abstractmethod
    def waiting_safe(self, by: By, selector: str, timeout: float) -> bool:
        """Ожидает появление элемента на странице. Если элемент появился, немедленно завершает
        работу и возвращает True. Если время ожидания вышло, а элемент не появился -
        возвращает False.

        :param by: Локатор, определяет стратегию поиска элемента, появление которого ожидаем.
        :param selector: Ключ поиска.
        :param timeout: Максимальное время ожидания.
        :return: Результат ожидания, True - элемент появился, False - вышло время ожидания,
            элемент не появился.
        """
        pass

    @abstractmethod
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
        pass

    @abstractmethod
    def get_current_url(self) -> str:
        """Возвращает текущий URL"""
        pass

    @abstractmethod
    def get_proxy(self) -> dict | None:
        """Возвращает прокси, который установлен для браузера

        :return: Прокси.
        """
        pass

    @abstractmethod
    def clean_all_cookies(self) -> None:
        """Очищает куки браузера

        :return: None
        """
        pass
