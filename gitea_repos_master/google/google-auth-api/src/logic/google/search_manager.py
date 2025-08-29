from typing import Type

from isphere_exceptions.source import (
    SourceIncorrectDataDetected,
    SourceLimitError,
    SourceOperationFailure,
    SourceParseError,
)
from isphere_exceptions.success import NoDataEvent
from isphere_exceptions.worker import InternalWorkerError
from selenium.common.exceptions import WebDriverException
from worker_classes.logic.search_manager import SearchManager

from src.browser.browser import Browser
from src.browser.browser_options import BrowserOptions
from src.browser.google_selenium_browser import GoogleSeleniumBrowser
from src.captcha.google_captcha import GoogleCaptcha
from src.exceptions import GoogleSessionCaptchaDecodeError
from src.interfaces.abstract_response_adapter import ResponseAdapter
from src.logic.google.screen_explorer import ScreensExplorer
from src.logic.google.screen_repository import ScreensRepository
from src.telegram.bot import TelegramBot


class SearchGoogleManager(SearchManager):
    SCREENS_EXPLORER_CLS: Type[ScreensExplorer] | None = None
    RESPONSE_ADAPTER_CLS: Type[ResponseAdapter] | None = None
    SCREENS_REPOSITORY: ScreensRepository | None = None
    START_URL: str = ""
    TELEGRAM_BOT_MSG_PREFIX: str = ""

    def __init__(self, auth_data=None, logging=None) -> None:
        super().__init__()
        self.screens_explorer: ScreensExplorer | None = None
        self.screens_explorer_prepared = False
        self.main_page_prepared = False
        self.browser_options: BrowserOptions | None = None

    async def _prepare(self) -> None:
        if not self.SCREENS_EXPLORER_CLS:
            raise InternalWorkerError(message="SCREENS_EXPLORER_CLS not passed")

        if not self.SCREENS_REPOSITORY:
            raise InternalWorkerError(message="SCREENS_REPOSITORY not passed")

        if not self.screens_explorer_prepared:
            self.browser_options = (
                await BrowserOptions()
                .load_from_file()
                .set_random_screen_size()
                .set_proxy()
            )

            self.screens_explorer = self.SCREENS_EXPLORER_CLS(
                start_url=self.START_URL,
                browser=Browser.configure(
                    browser=GoogleSeleniumBrowser(),
                    options=self.browser_options.get_options(),
                ),
                screens_repository=self.SCREENS_REPOSITORY,
                captcha_service=GoogleCaptcha(),
            )

            tb = TelegramBot()
            tb.message_prefix = self.TELEGRAM_BOT_MSG_PREFIX
            self.screens_explorer.new_screen_event.append(tb.send_files_from_path)

            self.screens_explorer_prepared = True

        # переходим на стартовую страницу - START_URL
        if self.screens_explorer and not self.main_page_prepared:
            try:
                self.screens_explorer.prepare_browser()
                self.main_page_prepared = True
            except SourceOperationFailure:
                self.main_page_prepared = False
                self.screens_explorer_prepared = False
                self.screens_explorer.close_browser()

    async def _clean(self) -> None:
        self.screens_explorer_prepared = False
        self.main_page_prepared = False
        if self.screens_explorer:
            self.screens_explorer.browser.close_browser()
            self.screens_explorer = None

    async def _is_ready(self) -> bool:
        return self.screens_explorer_prepared and self.main_page_prepared

    async def _search(
        self, phone_or_email: str, redelivered: bool = True, *args, **kwargs
    ) -> list[dict]:
        if not self.RESPONSE_ADAPTER_CLS:
            raise InternalWorkerError(message="RESPONSE_ADAPTER_CLS not passed")

        await self._prepare()

        if not self.screens_explorer:
            raise SourceOperationFailure()

        self.screens_explorer.result = {}

        try:
            self.main_page_prepared = False
            response = await self.screens_explorer.search(phone_or_email)
        except (
            SourceIncorrectDataDetected,
            NoDataEvent,
            GoogleSessionCaptchaDecodeError,
        ) as e:
            raise e
        except WebDriverException as e:
            self.__restart_browser()
            raise SourceOperationFailure(message=str(e).replace("\n", " "))
        except Exception as e:
            self.__restart_browser()
            if redelivered:
                SourceLimitError.DEFAULT_MESSAGE = (
                    "Превышен лимит попыток получить данные пользователя"
                )
                raise SourceLimitError()
            raise e

        if not response:
            self.__restart_browser()
            raise SourceParseError()

        return self.RESPONSE_ADAPTER_CLS.cast(response)

    def __restart_browser(self) -> None:
        self.screens_explorer_prepared = False
        if self.screens_explorer:
            self.screens_explorer.close_browser()
        if self.browser_options:
            self.browser_options.clear_proxy()
