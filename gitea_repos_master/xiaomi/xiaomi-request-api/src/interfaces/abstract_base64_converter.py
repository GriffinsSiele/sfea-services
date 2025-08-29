"""
Модуль содержит интерфейс для работы с конвертером изображений.
"""

from abc import ABC, abstractmethod


class AbstractBase64Converter(ABC):
    """
    Интерфейс для работы с конвертером изображений base64 в bytes.
    """

    @staticmethod
    @abstractmethod
    def covert_to_bytes(base64_string: str) -> bytes:
        """Конвертирует изображение base64 в bytes

        :param base64_string: Изображение в формате base64.
        :response: Изображение в формате bytes.
        """
        pass
