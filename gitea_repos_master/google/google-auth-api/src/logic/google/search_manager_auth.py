from src.config.app import ConfigApp
from src.logic.adapters.response_auth import ResponseAuthAdapter
from src.logic.google.connections_auth import screens_repository
from src.logic.google.screen_explorer_auth import ScreensAuthExplorer
from src.logic.google.search_manager import SearchGoogleManager


class SearchGoogleAuthManager(SearchGoogleManager):
    SCREENS_EXPLORER_CLS = ScreensAuthExplorer
    RESPONSE_ADAPTER_CLS = ResponseAuthAdapter
    SCREENS_REPOSITORY = screens_repository
    START_URL = ConfigApp.START_URL_AUTH
    TELEGRAM_BOT_MSG_PREFIX = "google-auth"
