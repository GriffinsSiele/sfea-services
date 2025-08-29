from isphere_exceptions.source import SourceError

from src.logic.repository.screen import Screen


class ExplorerFields:
    def __init__(self) -> None:
        self._main_page: Screen | None = None
        self._captcha_page: Screen | None = None

    @property
    def main_page(self) -> Screen:
        if not self._main_page:
            raise SourceError("Main page is not loaded")
        return self._main_page

    @main_page.setter
    def main_page(self, page: Screen) -> None:
        self._main_page = page

    @property
    def captcha_page(self) -> Screen:
        if not self._captcha_page:
            raise SourceError("Captcha screen is not loaded")
        return self._captcha_page

    @captcha_page.setter
    def captcha_page(self, page: Screen) -> None:
        self._captcha_page = page
