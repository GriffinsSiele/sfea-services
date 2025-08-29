"""
Модуль содержит класс SessionAdapter, который подготавливает сессию к сохранению исключая лишние поля.
"""

from datetime import datetime

from pydash import get, omit

from src.logger.context_logger import logging
from src.utils import get_params_from_url, str_to_dict


class SessionAdapter:
    @staticmethod
    def cast(session: dict) -> dict:
        """Подготавливает сессию к сохранению

        :param session: Сессия
        :return: Подготовленная к сохранению сессия
        """
        casted_session = {}
        params = get(session, "request_url", "")
        casted_session["params"] = get_params_from_url(params)
        cookies = get(session, "request_headers.cookie", "")
        casted_session["cookies"] = str_to_dict(cookies)
        proxy_id = get(session, "proxy_id")
        casted_session["proxy_id"] = proxy_id
        headers = get(session, "request_headers")
        casted_session["headers"] = omit(
            headers, "cookie", "content-length", "x-recaptcha-token"
        )
        if not all((params, cookies, headers, proxy_id)):
            logging.warning(
                'One of the parameters "params", "cookies", "headers" or "proxy_id" is empty'
            )
        return {"session": casted_session}

    @staticmethod
    def cast_for_update(session: dict) -> dict:
        """Подготавливает сессию к обновлению существующей сессии

        :param session: Сессия
        :return: Подготовленная к обновлению сессия
        """
        updated_fields = {
            "active": True,
            "created": datetime.now(),
            "count_use": 0,
            "count_success": 0,
            "last_use": None,
            "next_use": None,
        }
        return {**SessionAdapter.cast(session), **updated_fields}
