from src.config.settings import COUNT_SESSIONS_NAME_PERSON, MONGO_COLLECTION_NAME_PERSON
from src.logic.handlers.prolong.prolong_handler_name import SessionProlongName
from src.logic.samsung.session_maker_name import SessionMakerName
from src.logic.samsung.session_manager_common import SamsungSearchManagerCommon
from src.logic.session_storage.filter_constructor_person import (
    FilterConstructorNamePerson,
)


class SamsungSearchManagerName(SamsungSearchManagerCommon):
    """Управляет сессиями person"""

    mongo_collection = MONGO_COLLECTION_NAME_PERSON

    count_session_target = COUNT_SESSIONS_NAME_PERSON

    session_maker_cls = SessionMakerName
    filter_constructor = FilterConstructorNamePerson
    prolong_sessions_handler = SessionProlongName
