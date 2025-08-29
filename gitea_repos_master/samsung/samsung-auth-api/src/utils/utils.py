import datetime
from typing import Any, Callable

from isphere_exceptions.success import NoDataEvent

from src.logger.context_logger import logging


class ExtStr(str):
    """Расширяет функционал базового класса str."""

    def inline(self) -> str:
        """Заменяет переносы строки пробелами.

        :return: Строка
        """
        return self.replace("\n", " ")

    def short(self, size: int = 100) -> str:
        """Сокращает длину строки до указанного значения

        :param size: Максимальный размер строки
        :return: Строка
        """
        return self.inline() if len(self) < size else self.inline()[:size] + "..."

    def short_html(self, size: int = 100) -> str:
        """Сокращает длину HTML до указанного значения.
        Перед этим удаляет пробелы в начале и конце HTML.

        :param size: Максимальный размер строки
        :return: Строка
        """
        return ExtStr(self.strip()).short(size)


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
            except NoDataEvent as e:
                logging.info(f"Step {step_number}. Success.")
                raise e
            except Exception as e:
                logging.info(f"Step {step_number}. Failure. {e}")
                raise e

        return wrapper

    return inner


def now() -> int:
    """Возвращает текущую дату и время в формате unix epoch.

    :return: Unix epoch время и дата.
    """
    return int(datetime.datetime.now().timestamp())
