from abc import ABC, abstractmethod

from selenium.webdriver.common.by import By

from src.logic.google.configured_screen import ConfiguredScreen


class AbstractScreensRepository(ABC):
    @abstractmethod
    def add_page(self, key: str, value: ConfiguredScreen) -> None:
        pass

    @abstractmethod
    def get_page(self, key: str) -> ConfiguredScreen | None:
        pass

    @abstractmethod
    def get_main_definitions_from_all_pages(
        self, except_list: tuple[str, ...]
    ) -> list[tuple[By, str]]:
        pass

    @abstractmethod
    def get_all_page_titles(self) -> list[str]:
        pass

    @abstractmethod
    def get_all_page_links(self) -> list[tuple[By, str]]:
        pass
