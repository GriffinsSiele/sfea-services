import time
from functools import wraps
from typing import Any, Callable

from isphere_exceptions.success import NoDataEvent

from src.exceptions.exceptions import SessionCaptchaDecodeWarning
from src.logger import logging


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


def informer(step_number: int, step_message: str) -> Callable:
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
            except (SessionCaptchaDecodeWarning, NoDataEvent) as e:
                logging.info(
                    f"Step {step_number}. Success, completed in {elapsed_time(start)} seconds."
                )
                raise e
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


def several_attempts_async(attempts: int) -> Callable:
    """Декоратор, пытается несколько раз выполнить декорируемую функцию.
    Если функция поднимает исключение - логирует исключение и пытается выполнить функцию по новой.
    Если количество попыток исчерпано - поднимает последнее исключение функции.

    :param attempts: Количество попыток выполнить функцию.
    :return: Декорированная функция.
    """

    def outer_wrapper(func: Callable) -> Callable:
        @wraps(func)
        async def wrapper(*args, **kwargs) -> Any:
            exception: Exception | None = None
            for i in range(attempts):
                try:
                    result = await func(*args, **kwargs)
                    return result
                except Exception as e:
                    exception = e
                    logging.warning(
                        f'The function "{func.__name__}" raise {e}, attempt {i + 1} of {attempts}'
                    )
            if exception:
                raise exception

        return wrapper

    return outer_wrapper
