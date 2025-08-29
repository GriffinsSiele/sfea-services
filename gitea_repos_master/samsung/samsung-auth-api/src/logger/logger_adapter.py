import contextvars
import logging as base_logging
from typing import Any, MutableMapping

request_id_contextvar = contextvars.ContextVar("logger_context", default="")


class LoggingSearchKeyAdapter(base_logging.LoggerAdapter):
    """Адаптер для логера."""

    def __init__(self, logger: base_logging.Logger, wrapper: str = "||") -> None:
        """Инициализация адаптера.

        :param logger: Логер.
        :param wrapper: Обрамление контекста (по умолчанию "||").
        """
        super().__init__(logger)
        self.start, self.end = self.__parse_wrapper(wrapper)

    def process(self, msg, kwargs) -> tuple[str, MutableMapping[str, Any]]:
        """Обрабатывает сообщение логера, добавляет контекст в сообщение, если он задан.

        :param msg: Сообщение логера.
        :param kwargs: Аргументы сообщения.
        :return: Обработанное сообщение, аргументы сообщения.
        """
        context = self.get_context_message()
        if not context:
            return msg, kwargs
        return f"{self.start}{context}{self.end} - {msg}", kwargs

    @staticmethod
    def __parse_wrapper(wrapper) -> tuple[str, str]:
        """Подготавливает обрамление контекста.

        :param wrapper: Обрамление контекста.
        :return: Кортеж строк, которые будут использоваться для обрамления.
        """
        if wrapper and len(wrapper) == 2:
            return wrapper
        return "", ""

    @staticmethod
    def get_context_message() -> str:
        """Возвращает контекст из текущего запроса.

        :return: Контекст.
        """
        try:
            return request_id_contextvar.get()
        except LookupError:
            return ""
