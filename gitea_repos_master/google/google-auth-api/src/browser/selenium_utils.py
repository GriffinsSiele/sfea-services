import logging
import pathlib
from functools import wraps
from typing import Any, Callable

from putils_logic.putils import PUtils
from selenium.common.exceptions import WebDriverException

from src.config.settings import DEFAULT_FOLDER

_current_path = pathlib.Path(__file__).parent.absolute()
_base_dir = PUtils.bp(_current_path, "..", "..")
_default_path = PUtils.bp(_base_dir, DEFAULT_FOLDER)


def webdriver_exception_handler(func) -> Callable:
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
