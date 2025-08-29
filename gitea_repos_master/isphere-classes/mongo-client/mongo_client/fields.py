from datetime import timedelta
from typing import Dict, Literal, Optional, Union

PeriodFormats = Literal[
    "days", "seconds", "microseconds", "milliseconds", "minutes", "hours", "weeks"
]
Period = Dict[Union[PeriodFormats, str], Union[int, float]]


class MongoFields:
    """Вспомогательный класс для хранения значений по умолчанию класса клиента Mongo"""

    def __init__(self):
        self.next_use = "next_use"
        self.last_use = "last_use"
        self.active = "active"
        self.session = "session"

        self.block_time: Period = {"hours": 8}
        self.lock_time: Period = {"minutes": 10}

        self.next_use_delay = 1

        self.default_filter = {self.active: True}
        self.default_sort = [(self.last_use, 1)]
        self.projection = {self.session: 1}

    @property
    def next_use(self) -> str:
        """Геттер на получение имени поля, отвечающего за timestamp блокировки сессии.

        :return: имя поля
        """
        return self._next_use

    @next_use.setter
    def next_use(self, value: str):
        """Сеттер на установку имени поля блокировки сессии

        :param value: имя поля
        """
        self._next_use = value

    @property
    def active(self) -> str:
        """Геттер на получение имени поля, отвечающего за полную блокировку сессии

        :return: имя поля
        """
        return self._active

    @active.setter
    def active(self, value: str):
        """Сеттер на установку имени поля полной блокировки сессии

        :param value: имя поля
        """
        self._active = value

    @property
    def session(self) -> str:
        """Геттер на получение имени поля, отвечающего за основные данные сессии

        :return: имя поля
        """
        return self._session

    @session.setter
    def session(self, value: str):
        """Сеттер на установку имени поля полной основных данных сессии

        :param value: имя поля
        """
        self._session = value

    @property
    def last_use(self) -> str:
        """Геттер на получение имени поля времени последнего использования сессии

        :return: имя поля
        """
        return self._last_use

    @last_use.setter
    def last_use(self, value: str):
        """Сеттер на установку имени поля времени последнего использования сессии

        :param value: имя поля
        """
        self._last_use = value

    @property
    def block_time(self) -> timedelta:
        """Геттер времени блокировки сессии

        :return: timedelta
        """
        return timedelta(**self._block_time)

    @block_time.setter
    def block_time(self, value: Period):
        """Сеттер на установку времени по умолчанию на блокировку сессии

        :param value: период блокировки, например, ``{'hours': 8}``, ``{'seconds': 120}``
        """
        self._block_time = value

    @property
    def lock_time(self) -> timedelta:
        """Геттер времени временной блокировки сессии

        :return: timedelta
        """
        return timedelta(**self._lock_time)

    @lock_time.setter
    def lock_time(self, value: Period):
        """Сеттер на установку времени по умолчанию на временную блокировку сессии

        :param value: период блокировки, например, ``{'hours': 8}``, ``{'seconds': 120}``
        """
        self._lock_time = value

    @property
    def default_filter(self) -> Dict:
        """Получение фильтра по умолчанию для операций выборки данных
        :return: dict фильтра
        """
        return self._default_filter

    @default_filter.setter
    def default_filter(self, filter_options: Dict):
        """Сеттер на установки фильтра по умолчанию

        :param value: период блокировки, например, ``{'hours': 8}``, ``{'seconds': 120}``
        """
        self._default_filter = filter_options

    @property
    def projection(self) -> Optional[Dict]:
        """Словарь для указания полей в выборке данных

        Аналог из SQL - ``select id, name from ... = {'id': 1, 'name': 1}``
        ``select * from ... = {}``

        :return: dict проекции
        """
        return self._projection

    @projection.setter
    def projection(self, options: Optional[Dict]):
        """Сеттер на установки проекции

        :param value: словарь, где ключи имена полей, значения 0 или 1
        """
        self._projection = options

    @property
    def next_use_delay(self) -> int:
        """Значение в секундах времени, на которое блокируется сессия сразу же после выборки ее из БД.

        Данное свойство необходимо для избежания ситуации, когда N обработчиков хотят
        параллельно брать одинаковую сессию. Сессия сразу же блокируется на M секунд.
        :return: время в сек
        """
        return self._next_use_delay

    @next_use_delay.setter
    def next_use_delay(self, value: int):
        """Сеттер установки моментальной блокировки сессии

        :param value: положительное число, в сек
        """
        self._next_use_delay = value if value >= 0 else 1
