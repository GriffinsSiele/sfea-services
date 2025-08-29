"""
Работа с репозиторием браузеров.
Содержит примеры расширения функционала браузера из репозитория, его настройку и запуск.
"""

import pathlib

from browsers_repository import Browser, CommonBrowser
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


class MyBrowser(CommonBrowser):
    """
    1. Наследуемся от CommonBrowser - класса со всеми методами для работы с браузерами.
    Добавляем свои методы.
    """

    @staticmethod
    def new_method():
        print("New method worked!")


def main() -> None:
    """
    2. Настраиваем и запускаем нужный браузер.
    Легко переключаемся между браузерами, меняется только первый метод и его аргумент,
    настройки без изменений.
    """
    browser: MyBrowser = (
        Browser(MyBrowser)  # указываем свой класс для работы с браузерами
        # меняем данный метод для переключения между браузерами
        .chrome_undetected(chromedriver)
        # остальные настройки без изменений
        .options()
        .proxy(proxy)
        .accept_languages()
        .window_size(1024, 768)
        # .headless()
        .get_browser()
    )

    """
    3. Работаем с браузером.
    Поддерживает закрытие браузера и его повторное использование.
    """
    browser.start_browser()
    browser.get(URL)
    browser.new_method()  # используем добавленный метод
    input("Press Enter to exit")

    """
    4. Закрываем браузер (особенно это важно для FireFox).
    """
    browser.close_browser()


if __name__ == "__main__":
    main()
