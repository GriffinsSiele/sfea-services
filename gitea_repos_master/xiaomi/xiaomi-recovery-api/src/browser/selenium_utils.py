from functools import wraps
from typing import Any, Callable

from selenium.common.exceptions import WebDriverException

from src.logger import logging


def webdriver_exception_handler(func) -> Callable:
    """Декоратор, логирует исключение, которое возникло в функции."""

    @wraps(func)
    def wrapper(*args, **kwargs) -> Any:
        try:
            return func(*args, **kwargs)
        except WebDriverException as e:
            exception_message = e.msg if e.msg else e
            logging.warning(
                f'Function "{func.__name__}", args: "{args}", kwargs: "{kwargs}" raise exception '
                + exception_message.replace("\n", " ")
            )

            raise e

    return wrapper
