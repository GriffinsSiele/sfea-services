import logging
import pathlib
from typing import Any

from putils_logic.putils import PUtils

from src.logic.google.connections_auth import (
    CAPTCHA_NOT_SOLVED_SCREENS,
    CAPTCHA_SCREENS_FOR_SOLVING,
    MAIN_PAGE_INPUT,
    MAIN_PAGE_TITLE,
    MAIN_SIMILAR_SCREENS,
)
from src.logic.google.screen_explorer import ScreensExplorer

_current_file_path = pathlib.Path(__file__).parent.absolute()
_root_dir = PUtils.bp(_current_file_path, "..", "..", "..")


class ScreensAuthExplorer(ScreensExplorer):
    main_page_title = MAIN_PAGE_TITLE
    main_page_input = MAIN_PAGE_INPUT
    main_similar_screens = MAIN_SIMILAR_SCREENS
    captcha_not_solved_screens = CAPTCHA_NOT_SOLVED_SCREENS
    captcha_screens_for_solving = CAPTCHA_SCREENS_FOR_SOLVING

    async def search(self, payload: Any) -> dict:
        """Запускает поиск.

        :param payload: телефон или email по которому будет осуществляться поиск.
        :return: словарь с результатами поиска.
        """
        try:
            return await super().search(payload)
        except Exception as e:
            if self.result.get("found"):
                logging.warning("Search was interrupted: " + str(e).replace("\n", " "))
                logging.info(f"Full path: {self.traveled_screens}.")
                logging.info(f"Search results: {self.result}")
                return self.result
            raise e
