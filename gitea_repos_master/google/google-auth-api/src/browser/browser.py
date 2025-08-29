import logging

from fake_useragent import UserAgent

from src.browser.selenium_browser import SeleniumBrowser
from src.interfaces.abstract_browser import AbstractBrowser


class Browser:
    """
    Настраивает браузер.

    Example:
    -------
    ``
    options = {
        "headless": false,
        "window_size": [1024, 768],
        "options": ["--no-sandbox", "--disable-blink-features=AutomationControlled"],
        "proxy": [
            "IP",
            port,
            "user",
            "password",
        ],
        "user_agent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36
                       (KHTML, like Gecko) Chrome/118.0.0.0 Safari/537.36",
        "fake_user_agent": true,
    }
    ``
    """

    @staticmethod
    def configure(
        options: dict, browser: AbstractBrowser = SeleniumBrowser()
    ) -> AbstractBrowser:
        """
        Настраивает браузер.

        :param options: словарь с настройками браузера.
        :param browser: браузер (не обязательный параметр).
        :return: настроенный экземпляр браузера.
        """
        if list_options := options.get("options"):
            for option in list_options:
                browser.options.add_argument(option)

        if "headless" in options.keys():
            browser.headless = bool(options.get("headless"))

        user_agent = options.get("user_agent")
        if not user_agent and options.get("fake_user_agent"):
            user_agent = UserAgent().chrome
            logging.info(f"Fake user-agent: {user_agent}")
        if user_agent:
            browser.options.add_argument(f"--user-agent={user_agent}")

        if proxy_config := options.get("proxy"):
            browser.proxy_extension.prepare(*proxy_config)
            browser.options.add_argument(
                f"--load-extension={browser.proxy_extension.directory}"
            )

        if window_size := options.get("window_size"):
            browser.window_size = window_size

        return browser
