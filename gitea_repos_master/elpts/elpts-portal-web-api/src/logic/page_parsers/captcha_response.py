import warnings

import pydash
from bs4 import BeautifulSoup
from bs4.builder import XMLParsedAsHTMLWarning
from isphere_exceptions.source import SourceOperationFailure, SourceParseError

from src.interfaces.abstract_page_parser import AbstractPageParser
from src.logger.context_logger import logging

warnings.filterwarnings("ignore", category=XMLParsedAsHTMLWarning)


class CaptchaResponseParser(AbstractPageParser):
    def set_page(self, page: str) -> "CaptchaResponseParser":
        self.soup = BeautifulSoup(page, "lxml")
        return self

    def parse(self) -> dict:
        if not self.soup:
            logging.error("Page not transferred.")
            raise SourceOperationFailure()

        search_result = {**self._get_errors(), **self._get_payload()}

        if pydash.is_equal(search_result, {"errors": []}):
            raise SourceParseError(
                message="Parsing the search results page returned a null result"
            )

        return search_result

    def _get_errors(self) -> dict:
        result = []
        if errors := self.soup.find("ul", class_="feedbackPanel"):
            for li in errors.find_all("li"):
                result.append(li.text.strip())
        return {"errors": result}

    def _get_payload(self) -> dict:
        data = {}
        if payload := self.soup.find("div", class_="portal-attr-item-common"):
            for key, value in zip(payload.find_all("label"), payload.find_all("span")):
                if key.text and value.text:
                    data[key.text] = value.text
        return data
