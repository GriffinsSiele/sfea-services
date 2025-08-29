from abc import ABC, abstractmethod

from bs4 import BeautifulSoup


class AbstractPageParser(ABC):
    soup: BeautifulSoup

    @abstractmethod
    def set_page(self, page: str) -> "AbstractPageParser":
        pass

    @abstractmethod
    def parse(self) -> dict:
        pass
