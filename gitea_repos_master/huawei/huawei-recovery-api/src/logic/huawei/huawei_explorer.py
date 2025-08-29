"""
Выполняет проверку наличия аккаунты на сайте huawei.

После отправки решения капчи, если капча решена не верно, на форме капчи обновляются изображения,
если карча решена верно, форма капчи удаляется, сайт переходит на страницу восстановления аккаунта.
Иногда появляется диалоговое окно с предложением перейти на страницу восстановления (ScreenNames.EXTRA).
"""

from isphere_exceptions.source import SourceError
from isphere_exceptions.success import NoDataEvent

from src.config.app import ConfigApp
from src.logger import logging
from src.logic.huawei.common_explorer import CommonExplorer
from src.logic.repository.screen_configurator_huawei import ScreenRepositoryConfigurator
from src.logic.repository.screen_names import ScreenNames
from src.utils import informer


class HuaweiExplorer(CommonExplorer):
    screen_repository_maker = ScreenRepositoryConfigurator
    MAIN_PAGE_URL = ConfigApp.MAIN_PAGE_URL_HUAWEI
    TARGET_SCREEN_WIDTH = 260
    GAP = 5

    @informer(step_number=4, step_message="Receiving and parsing the search result")
    async def _parse_response(self, key: str, data: str) -> dict:
        name, screen = self.browser.waiting_and_get_screens(
            ConfigApp.WAITING_RESULT,
            [ScreenNames.EXTRA, ScreenNames.RESULT_NOT_FOUND, ScreenNames.RESULT_FOUND],
            self.screen_repository,
        )
        if name == ScreenNames.EXTRA and screen is not None:
            self.browser.get_element_and_click(*screen.buttons[0])

            name, screen = self.browser.waiting_and_get_screens(
                ConfigApp.WAITING_RESULT,
                [ScreenNames.RESULT_NOT_FOUND, ScreenNames.RESULT_FOUND],
                self.screen_repository,
            )

        if name == ScreenNames.RESULT_NOT_FOUND:
            raise NoDataEvent()

        if name == ScreenNames.RESULT_FOUND and screen is not None:
            adapted_payload = {}
            if payload := self.browser.get_payload(screen):
                logging.info(f'Founded payload "{payload}"')
                adapted_payload = self.payload_adapter_cls.adapt(key, data, payload)
            return {"result": "Найден", "result_code": "FOUND", **adapted_payload}

        raise SourceError("The resulting screen is not recognized")
