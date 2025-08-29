import datetime
import random
import string
import time
from typing import Any, Callable

from isphere_exceptions import SuccessEvent
from worker_classes.utils import short

from src.logger.context_logger import logging
from src.logic.xiaomi.exceptions import SessionCaptchaDecodeWarning

random.seed()

ascii_and_digits = string.ascii_letters + string.digits


def now() -> int:
    """Возвращает текущую дату и время в формате unix epoch.

    :return: Unix epoch время и дата.
    """
    return int(datetime.datetime.now().timestamp())


def informer(step_number: int | float, step_message: str) -> Callable:
    """Выводит логи до начала работы функции и после ее завершения.

    :param step_number: Номер шага
    :param step_message: Сообщение
    :return: Callable
    """

    def inner(func: Callable) -> Callable:
        async def wrapper(*args, **kwargs) -> Any:
            start = time.time()
            logging.info(f"Step {step_number}. {step_message} ...")
            try:
                func_result = await func(*args, **kwargs)
                logging.info(
                    f"Step {step_number}. Success, completed in {elapsed_time(start)} seconds."
                )
                return func_result
            except (SuccessEvent, SessionCaptchaDecodeWarning) as e:
                logging.info(
                    f"Step {step_number}. Success, completed in {elapsed_time(start)} seconds."
                )
                raise e
            except Exception as e:
                logging.warning(
                    f"Step {step_number}. Failure, completed in {elapsed_time(start)} seconds. "
                    f"Error: {short(str(e))}"
                )
                raise e

        return wrapper

    return inner


def elapsed_time(start_time: float) -> float:
    """Возвращает разницу между переданным значением и текущим значением времени.

    :param start_time: Начало отсчета.
    :return: Разница в секундах, округленная до трех знаком после запятой.
    """
    return round(time.time() - start_time, 3)


def strip_str(input_str: str) -> str:
    """Удаляет из строки символы "\n", " ".

    :param input_str: Исходная строка.
    :return: Преобразованная строка.
    """
    ban = [r"\n", " "]
    for pattern in ban:
        input_str = input_str.replace(pattern, "")
    return input_str


def random_string(length: int) -> str:
    """Возвращает рандомнцю строку длинной length

    :param length: Длинна строки
    :return: Рандомная строка
    """
    return "".join(random.choices(ascii_and_digits, k=length))


def get_timestamp() -> int:
    """Возвращает timestamp с тремя знаками после запятой (int(timestamp * 1000)

    :return: timestamp
    """
    return int(round(time.time() * 1000))
