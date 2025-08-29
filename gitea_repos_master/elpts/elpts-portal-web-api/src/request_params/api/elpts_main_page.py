from requests import Response
from requests.cookies import RequestsCookieJar

from src.config import ConfigApp
from src.request_params.interfaces.elpts_html import HTMLParams


class ElPtsMainPage(HTMLParams):
    def __init__(self, *args, **kwargs):
        super().__init__(url=ConfigApp.MAIN_PAGE_URL, *args, **kwargs)

    def _create_http_response(self, body, response) -> Response:
        """Получает куки не только из последнего запроса, но и из всех переадресаций."""
        r = Response()
        r._content = body
        r.status_code = response.status
        r.headers = dict(response.headers)  # type: ignore
        r.encoding = response.get_encoding()
        r.reason = response.reason
        r.url = response.url

        jar = RequestsCookieJar()
        for key, value in response.cookies.items():
            jar.set(key, value._value)
        for response_history in response.history:
            for key, value in response_history.cookies.items():
                jar.set(key, value._value)
        r.cookies = jar

        r.request = response.request_info
        r.connection = response.connection  # type: ignore
        return r
