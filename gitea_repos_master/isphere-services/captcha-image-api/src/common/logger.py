import copy
import logging
import sys
from functools import wraps
from typing import Any, Sized

from src.config.logging_config import logging_settings


class CustomLogFormatter(logging.Formatter):
    def __init__(self, **kwargs):
        super().__init__(**kwargs)

    def formatMessage(self, record: logging.LogRecord) -> str:
        return super().formatMessage(record).replace("\n", " ").replace(" " * 4, "")

    def formatException(self, ei) -> str:
        return super().formatException(ei).replace("\n", " ")


class LoggerFormatToolKit:
    @classmethod
    def hide_fields(cls, v: dict[str, Any]):
        for key, value in v.items():
            if key in logging_settings.HIDDEN_LOG_FIELDS:
                v[key] = "..."
            elif isinstance(value, dict):
                cls.hide_fields(value)

    @classmethod
    def short(cls, v: Any, str_size: int = 100, list_size: int = 5):
        if isinstance(v, str):
            return v[:str_size] + ("" if len(v) <= str_size else "...")
        if isinstance(v, dict):
            return {cls.short(key): cls.short(value) for key, value in v.items()}
        if isinstance(v, Sized):
            return [cls.short(i) for i in v[:list_size]] + ([] if len(v) <= list_size else ["..."])  # type: ignore[index]
        return v

    @classmethod
    def format_body(cls, body_data: dict[str, Any]) -> dict[str, Any]:
        body_data_copy = copy.deepcopy(body_data)
        cls.hide_fields(body_data_copy)
        return cls.short(body_data_copy)


class Logger(LoggerFormatToolKit):
    def __init__(self, name: str, stream_messages: bool = True):
        self.name = name
        self.formatter = CustomLogFormatter(
            fmt="%(asctime)s - %(name)s - [%(levelname)s] - (%(filename)s).%(funcName)s(%(lineno)d) - %(message)s"
        )
        self.stream_messages = stream_messages

    def get_logger(self, _level: Any = logging.INFO):
        logger = logging.getLogger(self.name)
        logger.setLevel(_level)
        logger.propagate = False
        if self.stream_messages:
            handler = logging.StreamHandler(sys.stdout)
            handler.setLevel(_level)
            handler.setFormatter(self.formatter)
            logger.addHandler(handler)
        return logger


def initialize_core_loggers():
    entities_to_log = {
        "uvicorn.access",
        "gunicorn",
        "gunicorn.error",
    }
    for entity in entities_to_log:
        Logger(entity).get_logger()
    # configure sqlalchemy ORM logger to stream only in debug mode
    if logging_settings.LOG_LEVEL is logging.DEBUG:
        Logger("sqlalchemy.engine").get_logger(logging.DEBUG)
    else:
        Logger("sqlalchemy.engine", stream_messages=False).get_logger(logging.INFO)


def log_crud_operation(operation: str):
    def decorator(func):
        @wraps(func)
        async def wrapper(self, *args, **kwargs):
            result = await func(self, *args, **kwargs)
            if result is not None and result.id:
                self.logger.info(
                    f"{operation} operation, ENTITY_ID {result.id}: {result}"
                )
            return result

        return wrapper

    return decorator
