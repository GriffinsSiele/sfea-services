from src.config.app import ConfigApp
from src.logic.session_storage.filter_constructor_common import FilterConstructor


class FilterConstructorNamePerson(FilterConstructor):
    """Конструктор фильтров для работы с MongoDB"""

    session_max_use = ConfigApp.name_person.SESSION_MAX_USE
    time_session_prolong = ConfigApp.name_person.TIME_SESSION_PROLONG
    time_session_becomes_old = ConfigApp.name_person.TIME_SESSION_BECOMES_OLD
