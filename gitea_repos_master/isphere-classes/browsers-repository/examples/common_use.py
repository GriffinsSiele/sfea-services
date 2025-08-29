"""
Работа с репозиторием браузеров.
Содержит примеры настройки и запуска браузера, переключение между браузерами.
"""

import pathlib

from browsers_repository import AbstractBrowser, Browser
from browsers_repository.utils.utils import join_path

_current_path = pathlib.Path(__file__).parent.absolute()
_root_path = join_path(_current_path, "..")
geckodriver = join_path(_root_path, "driver", "geckodriver")
chromedriver = join_path(_root_path, "driver", "chromedriver")

URL = "https://ip.oxylabs.io/"

"""Настройки прокси"""
proxy = {
    "id": "****",  # не обязательный параметр
    "server": "127.0.0.1",
    "port": "8080",
    "login": "****",
    "password": "****",
}
"""или"""
# proxy = {
#     "http": "http://<простая аутентификация>...",
#     "https": "http://<простая аутентификация>...",
#     "id": "****",   # не обязательный параметр
# }
"""или"""
# proxy = {
#     "http": "http://<простая аутентификация>...",
#     "id": "****",   # не обязательный параметр
# }


def main() -> None:
    """
    1. Настраиваем и запускаем нужный браузер.
    Легко переключаемся между браузерами, меняется только первый метод и его аргумент,
    настройки без изменений.
    """

    browser: AbstractBrowser = (
        Browser()
        # для переключения браузера меняется только данный метод:
        .fire_fox_wire(geckodriver)
        # .headless()
        .options()
        .proxy(proxy)
        .accept_languages()
        .window_size(1024, 768)
        .get_browser()
    )
    # browser: AbstractBrowser = (
    #     Browser()
    #     .chrome_wire(chromedriver)
    #     # .headless()
    #     .options()
    #     .proxy(proxy)
    #     .accept_languages()
    #     .window_size(1024, 768)
    #     .get_browser()
    # )
    # browser: AbstractBrowser = (
    #     Browser()
    #     .chrome_undetected(chromedriver)
    #     .options()
    #     .proxy(proxy)
    #     .accept_languages()
    #     .window_size(1024, 768)
    #     .get_browser()
    # )
    # browser: AbstractBrowser = (
    #     Browser()
    #     .chrome(chromedriver)
    #     .options()
    #     .proxy(proxy)
    #     .accept_languages()
    #     .window_size(1024, 768)
    #     .get_browser()
    # )
    # browser: AbstractBrowser = (
    #     Browser()
    #     .firefox(geckodriver)
    #     .options()
    #     .proxy(proxy)
    #     .accept_languages()
    #     .window_size(1024, 768)
    #     .get_browser()
    # )
    """
    2. Работаем с браузером.
    Поддерживает закрытие браузера и его повторное использование.
    """
    browser.start_browser()
    browser.get(URL)
    print(browser.get_proxy())
    input("Press Enter to close browser")
    browser.close_browser()
    input("Press Enter to run created browser again")
    browser.start_browser()
    browser.get(URL)
    input("Press Enter to exit")

    """
    3. Закрываем браузер (особенно это важно для FireFox).
    """
    browser.close_browser()


if __name__ == "__main__":
    main()
