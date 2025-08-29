from abc import ABC, abstractmethod

from src.interfaces.abstract_browser import AbstractBrowser

ReceivedDataPart = dict[str, list | bool] | None


class AbstractScreenDispatcher(ABC):
    """Собирает и возвращает данные со страницы."""

    @abstractmethod
    def get_data(self, web_browser: AbstractBrowser) -> ReceivedDataPart:
        pass
