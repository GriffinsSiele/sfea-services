from browsers_repository.browsers.common_browser import CommonBrowser
from browsers_repository.builder.chrome_builder import ChromeBuilder
from browsers_repository.builder.chrome_undetected_builder import ChromeUndetectedBuilder
from browsers_repository.builder.chrome_wire_builder import ChromeWireBuilder
from browsers_repository.builder.firefox_builder import FireFoxBuilder
from browsers_repository.builder.firefox_wire_builder import FireFoxWireBuilder
from browsers_repository.logger.logger import log


class Browser:
    """Общий класс для создания и настройки браузеров."""

    def __init__(self, browser=CommonBrowser, logger=log) -> None:
        """Конструктор класса для создания и настройки браузеров.

        :param browser: Класс, который содержит методы для работы с браузером. Опционально, по умолчанию CommonBrowser.
        :param logger: Логгер, опционально, по умолчанию используется стандартный логер.
        :return: None.
        """
        self.browser = browser
        self.logger = logger

    def fire_fox_wire(self, geckodriver: str) -> FireFoxWireBuilder:
        """FireFox браузер, версия wire

        :param geckodriver: Путь к geckodriver.
        :return: FireFoxWireBuilder.
        """
        return FireFoxWireBuilder(geckodriver, self.browser, self.logger)

    def firefox(self, geckodriver: str) -> FireFoxBuilder:
        """FireFox браузер, обычная версия.

        :param geckodriver:  Путь к geckodriver.
        :return: FireFoxBuilder.
        """
        return FireFoxBuilder(geckodriver, self.browser, self.logger)

    def chrome_wire(self, chromedriver: str) -> ChromeWireBuilder:
        """Chrome браузер, версия wire

        :param chromedriver: Путь к chromedriver.
        :return: ChromeWireBuilder.
        """
        return ChromeWireBuilder(chromedriver, self.browser, self.logger)

    def chrome_undetected(self, chromedriver: str) -> ChromeUndetectedBuilder:
        """Chrome браузер, версия undetected.

        :param chromedriver:  Путь к chromedriver.
        :return: ChromeUndetectedBuilder.
        """
        return ChromeUndetectedBuilder(chromedriver, self.browser, self.logger)

    def chrome(self, chromedriver: str) -> ChromeBuilder:
        """Chrome браузер, обычная версия.

        :param chromedriver:  Путь к chromedriver.
        :return: ChromeBuilder.
        """
        return ChromeBuilder(chromedriver, self.browser, self.logger)
