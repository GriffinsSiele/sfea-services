import logging as base_logging

from src.logger.logger_adapter import LoggingSearchKeyAdapter


class ContextLogger:
    """Логер с контекстом"""

    loggers = (
        "uvicorn.error",
        "sentry_sdk.errors",
        "proxy_manager.core",
    )

    def __init__(self) -> None:
        for log in self.loggers:
            base_logging.getLogger(log).setLevel(base_logging.WARNING)

    @staticmethod
    def get_logger(name: str) -> LoggingSearchKeyAdapter:
        """Возвращает логер по переданному имени.

        :param name: Имя логера, который требуется вернуть.
        :return: Логер.
        """
        logger = base_logging.getLogger(name)
        return LoggingSearchKeyAdapter(logger)

    @staticmethod
    def get_root_logger() -> LoggingSearchKeyAdapter:
        """Возвращает корневой логер (с именем root)"""
        logger = base_logging.getLogger("root")
        return LoggingSearchKeyAdapter(logger)


logging = ContextLogger().get_root_logger()
