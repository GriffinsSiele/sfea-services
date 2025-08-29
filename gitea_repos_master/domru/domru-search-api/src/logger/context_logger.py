import logging
import sys
from typing import Any

from worker_classes.logger import Logger

from src.logger.logger_adapter import LoggingSearchKeyAdapter


class CustomLogFormatter(logging.Formatter):
    def __init__(self, **kwargs):
        super().__init__(**kwargs)

    def formatMessage(self, record: logging.LogRecord) -> str:
        return super().formatMessage(record).replace("\n", " ").replace(" " * 4, "")

    def formatException(self, ei) -> str:
        return super().formatException(ei).replace("\n", " ")


class ContextLogger:

    @classmethod
    def initialize_core_loggers(cls) -> None:
        loggers = (
            "gunicorn",
            "uvicorn.access",
            "charset_normalizer",
            "sentry_sdk.errors",
        )
        Logger().create()
        for log in loggers:
            cls.get_logger(log)

    @classmethod
    def get_logger(cls, name: str, _level: Any = logging.INFO) -> LoggingSearchKeyAdapter:
        """Возвращает логер по переданному имени.

        :param name: Имя логера, который требуется вернуть.
        :return: Логер.
        """
        logger = logging.getLogger(name)
        logger.setLevel(_level)
        logger.propagate = False
        handler = logging.StreamHandler(sys.stdout)
        handler.setLevel(_level)
        handler.setFormatter(
            CustomLogFormatter(
                fmt="%(asctime)s - %(name)s - [%(levelname)s] - (%(filename)s).%(funcName)s(%(lineno)d) - %(message)s"
            )
        )
        logger.addHandler(handler)
        return LoggingSearchKeyAdapter(logger)

    @classmethod
    def get_root_logger(cls) -> LoggingSearchKeyAdapter:
        """Возвращает корневой логер (с именем root)

        :return: Logger
        """
        return cls.get_logger("root")


context_logging = ContextLogger().get_root_logger()
