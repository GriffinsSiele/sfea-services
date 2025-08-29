"""
Выполняет проверку наличия аккаунты на сайте honor.

После отправки решения капчи, если капча решена не верно, изображения капчи обновляется,
если карча решена верно, форма капчи скрывается, сайт переходит на страницу восстановления аккаунта.
"""

from isphere_exceptions.source import SourceError
from isphere_exceptions.success import NoDataEvent

from src.config.app import ConfigApp
from src.exceptions.exceptions import SessionCaptchaDecodeWarning
from src.logger import logging
from src.logic.huawei.common_explorer import CommonExplorer
from src.logic.repository.screen_configurator_honor import ScreenRepositoryConfigurator
from src.logic.repository.screen_names import ScreenNames
from src.utils import informer


class HonorExplorer(CommonExplorer):
    screen_repository_maker = ScreenRepositoryConfigurator
    MAIN_PAGE_URL = ConfigApp.MAIN_PAGE_URL_HONOR
    TARGET_SCREEN_WIDTH = 220
    GAP = 0

    def _check_captcha_solution(self) -> None:
        if not self._check_captcha_slider():
            raise SourceError("Captcha slider is not usable")
        # Проверка, что окно капчи скрыто, следовательно, капча принята.
        # Если капча не принята, то окно остается и просто обновляется изображение.
        if not self.browser.waiting_element_becomes_unavailable(
            self.captcha_page.payloads[3], ConfigApp.WAITING_CAPTCHA_CHECK
        ):
            raise SessionCaptchaDecodeWarning("Captcha not accepted")

    @informer(step_number=4, step_message="Receiving and parsing the search result")
    async def _parse_response(self, key: str, data: str) -> dict:
        name, screen = self.browser.waiting_and_get_screens(
            ConfigApp.WAITING_RESULT,
            [ScreenNames.BLOCKED, ScreenNames.RESULT_NOT_FOUND, ScreenNames.RESULT_FOUND],
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

        if name == ScreenNames.BLOCKED:
            # Аккаунт заблокирован на 24 часа
            logging.warning(f"Account {data} is temporarily blocked")
            return {"result": "Найден", "result_code": "FOUND"}

        raise SourceError("The resulting screen is not recognized")
