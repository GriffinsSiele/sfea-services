from datetime import datetime


def now() -> int:
    """Возвращает текущую дату и время в формате unix epoch.

    :return: Unix epoch время и дата.
    """
    return int(datetime.now().timestamp())
