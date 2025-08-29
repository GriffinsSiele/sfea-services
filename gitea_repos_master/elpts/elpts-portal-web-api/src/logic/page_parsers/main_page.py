from bs4 import BeautifulSoup
from isphere_exceptions.source import SourceParseError
from isphere_exceptions.worker import InternalWorkerError

from src.interfaces import AbstractMainPageParser


class MainPageParser(AbstractMainPageParser):
    def set_page(self, page: str) -> "MainPageParser":
        self.soup = BeautifulSoup(page, "lxml")
        return self

    def parse_vin(self) -> dict:
        if not self.soup:
            raise InternalWorkerError(message="Main page not transferred.")
        return {
            "csrf_token_value": self._get_csrf_token(),
            "form_link_index": self._get_form_link_index(),
            "input_id": self._get_input_id(),
        }

    def parse_epts(self) -> dict:
        if not self.soup:
            raise InternalWorkerError(message="Main page not transferred.")
        return {
            "csrf_token_value": self._get_csrf_token(),
            "form_link_index": self._get_form_link_index(form_number=0),
            "input_id": self._get_input_id(form_number=0),
        }

    def _get_csrf_token(self) -> str:
        if csrf_token := self.soup.find("meta", attrs={"name": "csrf-token-value"}):
            return csrf_token.get("content")
        raise SourceParseError(message='Failed to get "csrf_token_value"')

    def _get_form_link_index(self, form_number: int = 1) -> str:
        if form_link := self.soup.find_all("form"):
            return form_link[form_number].get("action")[8:11]
        raise SourceParseError(message='Failed to get "form_link_index"')

    def _get_input_id(self, form_number: int = 1) -> str:
        if input_ := self.soup.find_all("input", attrs={"type": "hidden"}):
            return input_[form_number].get("id")
        raise SourceParseError(message='Failed to get "input_id"')
