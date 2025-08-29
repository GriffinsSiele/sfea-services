from datetime import datetime, timedelta


class FilterConstructor:
    """Конструктор фильтров для работы с MongoDB"""

    active = "active"
    next_use = "next_use"
    last_use = "last_use"
    created = "created"
    count_use = "count_use"

    session_max_use: int = 0
    time_session_prolong: dict = {}
    time_session_becomes_old: dict = {}

    def __init__(self):
        self.active_part = {self.active: True}

    def get_not_used_part(self) -> dict:
        return {
            "$or": [
                {self.next_use: {"$lt": datetime.now()}},
                {self.next_use: None},
            ],
        }

    def _get_variable_part(self, filter_time: dict) -> dict:
        """Возвращает фильтр, в котором время последнего использования сессий старше
        переданного времени filter_time или, если время последнего использования сессии равно null,
        время создания сессии старше времени filter_time.

        :param filter_time: Время в формате datetime.timedelta (Например {"minutes": 8})
        :return: Фильтр для поиска сессий.
        """
        return {
            "$or": [
                {self.last_use: {"$lt": datetime.now() - timedelta(**filter_time)}},
                {
                    "$and": [
                        {self.last_use: None},
                        {
                            self.created: {
                                "$lt": datetime.now() - timedelta(**filter_time)
                            }
                        },
                    ]
                },
            ]
        }

    def _get_still_alive_part(self, filter_time: dict) -> dict:
        """Возвращает фильтр, в котором время последнего использования сессий меньше
        переданного времени filter_time или, если время последнего использования сессии равно null,
        время создания сессии меньше времени filter_time.

        :param filter_time: Время в формате datetime.timedelta (Например {"minutes": 8})
        :return: Фильтр для поиска сессий.
        """
        return {
            "$or": [
                {self.last_use: {"$gt": datetime.now() - timedelta(**filter_time)}},
                {self.created: {"$gt": datetime.now() - timedelta(**filter_time)}},
            ],
        }

    def get_outdated_sessions_filter(self) -> dict:
        """Возвращает фильтр для поиска сессий у которых истекло время жизни
        или превышено количество использования.

        :return: Фильтр для поиска сессий.
        """
        return {
            "$and": [
                self.active_part,
                self.get_not_used_part(),
                {
                    "$or": [
                        {self.count_use: {"$gte": self.session_max_use}},
                        self._get_variable_part(self.time_session_becomes_old),
                    ]
                },
            ]
        }

    def get_prolong_filter(self) -> dict:
        """Возвращает фильтр для поиска сессий у которых истекает время жизни
        и количество использования сессий позволяет его продлить.

        :return: Фильтр для поиска сессий.
        """
        return {
            "$and": [
                self.active_part,
                self.get_not_used_part(),
                self._get_variable_part(self.time_session_prolong),
                self._get_still_alive_part(self.time_session_becomes_old),
                # нет смысла продливать жизнь сессии запросом, если это последний запрос для сессии:
                {self.count_use: {"$lt": self.session_max_use - 1}},
            ]
        }
