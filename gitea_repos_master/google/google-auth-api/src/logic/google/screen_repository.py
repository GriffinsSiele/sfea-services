from functools import lru_cache

from selenium.webdriver.common.by import By

from src.interfaces.abstract_screens_repository import AbstractScreensRepository
from src.logic.google.configured_screen import ConfiguredScreen


class ScreensRepository(AbstractScreensRepository):
    def __init__(self):
        self.screens = {}

    def add_page(self, title: str, obj: ConfiguredScreen) -> None:
        if not isinstance(title, str):
            raise ValueError(f"{title} must be of type str")
        if not isinstance(obj, ConfiguredScreen):
            raise ValueError(f"{obj} must be an instance of the ConfiguredScreen class")
        self.screens[title] = obj

    def get_page(self, title: str) -> ConfiguredScreen | None:
        if not isinstance(title, str):
            raise ValueError(f"{title} must be of type str")
        return self.screens.get(title, None)

    @lru_cache
    def get_main_definitions_from_all_pages(
        self, except_list: tuple[str, ...]
    ) -> list[tuple[By, str]]:
        main_definitions = []
        for title, screen in self.screens.items():
            if title not in except_list and screen.main_definitions:
                main_definitions += screen.main_definitions
        return main_definitions

    @lru_cache
    def get_all_page_titles(self) -> list[str]:
        return list(self.screens.keys())

    @lru_cache
    def get_all_page_links(self) -> list[tuple[By, str]]:
        links = []
        for screen in self.screens.values():
            if not screen.follow:
                continue
            for link in screen.follow:
                temp_link = link.get("link")
                if temp_link and temp_link not in links:
                    links.append(temp_link)
        return links
