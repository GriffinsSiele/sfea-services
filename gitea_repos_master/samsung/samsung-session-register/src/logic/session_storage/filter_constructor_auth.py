from src.config.app import ConfigApp
from src.logic.session_storage.filter_constructor_common import FilterConstructor


class FilterConstructorAuth(FilterConstructor):
    """Конструктор фильтров для работы с MongoDB"""

    session_max_use = ConfigApp.auth.SESSION_MAX_USE
    time_session_prolong = ConfigApp.auth.TIME_SESSION_PROLONG
    time_session_becomes_old = ConfigApp.auth.TIME_SESSION_BECOMES_OLD
