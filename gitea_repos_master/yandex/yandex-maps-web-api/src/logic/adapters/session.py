import re

from isphere_exceptions.session import SessionLocked
from pydash import get


class AuthAdapter:
    @staticmethod
    def cast(response):
        cookies = dict(response.cookies)
        yandex_u_id = get(cookies, "yandexuid")
        if not yandex_u_id:
            raise SessionLocked('Not extracted field "yandexuid"')

        html = response.text
        csrf_token = get(re.findall('"csrfToken":"(.*?:\d+)"', html), "0")
        if not csrf_token:
            raise SessionLocked('Not extracted field "csrf_token"')

        session_id = get(re.findall('"sessionId":"([\d_]+)"', html), "0")
        if not session_id:
            raise SessionLocked('Not extracted field "session_id"')

        return {
            "csrf_token": csrf_token,
            "session_id": session_id,
            "cookies": cookies,
        }
