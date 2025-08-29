from datetime import datetime

from isphere_exceptions import ISphereException


def now() -> int:
    """Возвращает текущую дату и время в формате unix epoch.

    :return: Unix epoch время и дата.
    """
    return int(datetime.now().timestamp())


def e_to_dict(e: Exception):
    message = (
        e.to_response()
        if isinstance(e, ISphereException) and hasattr(e, "to_response")
        else str(e)
    )
    code = e.code if hasattr(e, "code") else 500
    return (code, message)
