"""
Модуль содержит интерфейс для работы с сервисом решения капчи.
"""

from abc import ABC, abstractmethod


class AbstractCaptchaService(ABC):
    """
    Интерфейс для работы с сервисом решения капчи.
    """

    @abstractmethod
    async def post_captcha(self, image: bytes, timeout: int = 0) -> dict | None:
        """Отправляет изображение для решения капчи.

        :param image: Изображение с капчей.
        :param timeout: Максимальное время в течении которого требуется вернуть решение капчи.
        :return: Словарь с результатами решения.
        """
        pass

    @abstractmethod
    async def result_report(self, task_id: str, correct: bool) -> bool:
        """Отправляет отчет о решении капчи.

        :param task_id: ID задачи по решению капчи.
        :param correct: Результат решения.
        :return: Результат отправки решения.
        """
        pass
