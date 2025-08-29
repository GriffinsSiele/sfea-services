"""
Модуль содержит интерфейс для получения данных с сайта Samsung.
"""

from abc import ABC, abstractmethod

from requests import Response


class AbstractSamsungSource(ABC):
    """Интерфейс для получения данных с сайта Samsung"""

    def __init__(self, *args, **kwargs) -> None:
        pass

    @abstractmethod
    def request(self, *args, **kwargs) -> Response:
        pass
