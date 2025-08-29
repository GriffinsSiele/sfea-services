from src.config.settings import COUNT_SESSIONS_AUTH, MONGO_COLLECTION_AUTH
from src.logic.handlers.prolong import SessionProlongAuth
from src.logic.samsung.session_maker_auth import SessionMakerAuth
from src.logic.samsung.session_manager_common import SamsungSearchManagerCommon
from src.logic.session_storage.filter_constructor_auth import FilterConstructorAuth


class SamsungSearchManagerAuth(SamsungSearchManagerCommon):
    """Управляет сессиями auth"""

    mongo_collection = MONGO_COLLECTION_AUTH

    count_session_target = COUNT_SESSIONS_AUTH
    session_maker_cls = SessionMakerAuth
    filter_constructor = FilterConstructorAuth
    prolong_sessions_handler = SessionProlongAuth
