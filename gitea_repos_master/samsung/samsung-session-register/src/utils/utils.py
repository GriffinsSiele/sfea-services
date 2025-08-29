import time
from typing import Any, Callable
from urllib.parse import urlparse

from src.logger.context_logger import logging


class ExtStr(str):
    """
    Расширенный класс str с дополнительными методами.
    """

    def inline(self) -> str:
        """Заменяет переносы строки пробелами.

        :return: Строка.
        """
        return self.replace("\n", " ")

    def short(self, size: int = 100) -> str:
        """Удаляет переносы строк и сокращает длину получившейся строки до указанного значения size.

        :param size: Максимальный размер строки
        :return: Строка
        """
        return self.inline() if len(self) < size else self.inline()[:size] + "..."


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
            except Exception as e:
                logging.warning(
                    f"Step {step_number}. Failure, completed in {elapsed_time(start)} seconds. "
                    f"Error: {ExtStr(e).inline()}"
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


def str_to_dict(data: str, separator: str = "; ") -> dict | None:
    """Парсит строку формата <ключ>=<значение>; в словарь.

    :param data: Строка формата <ключ>=<значение>.
    :param separator: Разделитель пар <ключ>=<значение>.
    :return: Словарь.
    """
    pairs = data.split(separator)
    result = {}

    try:
        for pair in pairs:
            if "=" in pair:
                key, value = pair.split("=", 1)
                result[key.strip()] = value.strip()
        return result
    except Exception as e:
        logging.error(f"Error converting string to dict: {ExtStr(e).inline()}")

    return None


def get_params_from_url(url: str) -> dict | None:
    """Извлекает параметры запроса из URL адреса и возвращает их в виде словаря.

    :param url: URL адрес.
    :return: Словарь из параметров запроса.
    """
    parsed_url = urlparse(url)
    params = parsed_url.query
    return str_to_dict(data=params, separator="&")
