from src.config.app import ConfigApp
from src.logic.adapters.response_name import ResponseNameAdapter
from src.logic.google.connections_name import screens_repository_name
from src.logic.google.screen_explorer_name import ScreensNameExplorer
from src.logic.google.search_manager import SearchGoogleManager


class SearchGoogleNameManager(SearchGoogleManager):
    SCREENS_EXPLORER_CLS = ScreensNameExplorer
    RESPONSE_ADAPTER_CLS = ResponseNameAdapter
    SCREENS_REPOSITORY = screens_repository_name
    START_URL = ConfigApp.START_URL_NAME
    TELEGRAM_BOT_MSG_PREFIX = "google-name"
