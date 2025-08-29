from src.config.settings import COUNT_SESSIONS_NAME_PERSON, MONGO_COLLECTION_NAME_PERSON
from src.logic.handlers.prolong import SessionProlongPerson
from src.logic.samsung.session_maker_person import SessionMakerPerson
from src.logic.samsung.session_manager_common import SamsungSearchManagerCommon
from src.logic.session_storage.filter_constructor_person import (
    FilterConstructorNamePerson,
)


class SamsungSearchManagerPerson(SamsungSearchManagerCommon):
    """Управляет сессиями person"""

    mongo_collection = MONGO_COLLECTION_NAME_PERSON

    count_session_target = COUNT_SESSIONS_NAME_PERSON
    session_maker_cls = SessionMakerPerson
    filter_constructor = FilterConstructorNamePerson
    prolong_sessions_handler = SessionProlongPerson
