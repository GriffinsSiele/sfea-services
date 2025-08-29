import re
import warnings

from bs4 import BeautifulSoup
from bs4.builder import XMLParsedAsHTMLWarning
from isphere_exceptions.source import SourceParseError
from isphere_exceptions.worker import InternalWorkerError

from src.config import ConfigApp
from src.interfaces.abstract_page_parser import AbstractPageParser

warnings.filterwarnings("ignore", category=XMLParsedAsHTMLWarning)


class PostResponseParser(AbstractPageParser):
    page: str

    def set_page(self, page: str) -> "PostResponseParser":
        self.page = page
        self.soup = BeautifulSoup(page, "lxml")
        return self

    def parse(self) -> dict:
        if not self.soup:
            raise InternalWorkerError(message="Captcha page not transferred")
        return {
            "captcha_link": self._get_captcha_link(),
            "captcha_link_index": self._get_captcha_link_index(),
            "captcha_input_id": self._get_captcha_input_id(),
        }

    def _get_captcha_link(self) -> str:
        if img := self.soup.find("img", class_="captcha-img"):
            return ConfigApp.BASE_URL + "/portal" + img.get("src")[1:]
        raise SourceParseError(message='Failed to get "captcha_link"')

    def _get_captcha_input_id(self) -> str:
        if input_ := self.soup.find("input", attrs={"type": "hidden"}):
            return input_.get("id", "")
        raise SourceParseError(message='Failed to get "captcha_input_id"')

    def _get_captcha_link_index(self) -> str:
        if match := re.findall(r"index\?(0-[0-3]).IBehaviorListener", self.page):
            return str(match[0])
        raise SourceParseError(message='Failed to get "captcha_link_index"')
