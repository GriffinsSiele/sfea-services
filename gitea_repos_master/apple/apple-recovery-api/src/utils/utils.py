import datetime
from typing import Any, Callable

from src.logger.context_logger import logging


def now() -> int:
    """Возвращает текущую дату и время в формате unix epoch.

    :return: Unix epoch время и дата.
    """
    return int(datetime.datetime.now().timestamp())


def informer(step_number: int, step_message: str) -> Callable:
    """Декоратор, выводит логи до начала работы функции и после ее завершения.

    :param step_number: Шаг в формате int
    :param step_message: Сообщение на шаге.
    :return: Декорированная функция.
    """

    def inner(func: Callable) -> Callable:
        async def wrapper(*args, **kwargs) -> Any:
            logging.info(f"Step {step_number}. {step_message} ...")
            try:
                func_result = await func(*args, **kwargs)
                logging.info(f"Step {step_number}. Success.")
                return func_result
            except Exception as e:
                logging.warning(f"Step {step_number}. Failure. {e}")
                raise e

        return wrapper

    return inner


def strip_str(input_str: str) -> str:
    """Удаляет из строки символы "\n", " ".

    :param input_str: Исходная строка.
    :return: Преобразованная строка.
    """
    ban = [r"\n", " "]
    for pattern in ban:
        input_str = input_str.replace(pattern, "")
    return input_str
