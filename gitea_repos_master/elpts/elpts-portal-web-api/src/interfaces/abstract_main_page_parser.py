from abc import ABC, abstractmethod

from bs4 import BeautifulSoup


class AbstractMainPageParser(ABC):
    soup: BeautifulSoup

    @abstractmethod
    def set_page(self, page: str) -> "AbstractMainPageParser":
        pass

    @abstractmethod
    def parse_vin(self) -> dict:
        pass

    @abstractmethod
    def parse_epts(self) -> dict:
        pass
