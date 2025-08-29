import logging

from isphere_exceptions.proxy import ProxyBlocked
from isphere_exceptions.success import NoDataEvent
from isphere_exceptions.worker import UnknownError
from requests.exceptions import ProxyError

from src.logic.adapters.response import ResponseAdapter
from src.logic.yandex.validation import ResponseValidation
from src.request_params.api.search import SearchParams


class SearchPhoneRequests:
    def __init__(self, cookies=None, csrf_token=None, session_id=None, proxy=None):
        self.cookies = cookies
        self.csrf_token = csrf_token
        self.session_id = session_id
        self.proxy = proxy

    async def search(self, phone):
        sp = SearchParams(cookies=self.cookies, proxy=self.proxy)
        sp.set_query(
            csrf_token=self.csrf_token, session_id=self.session_id, payload=phone
        )

        try:
            response = await sp.request()
        except (ProxyError, ConnectionError) as e:
            raise ProxyBlocked(e)
        except Exception as e:
            logging.error(e)
            raise UnknownError(e)

        logging.info(f"Search for phone {phone} responded: {response.status_code}")
        response = ResponseValidation.validate_response(response)
        result = ResponseAdapter.cast(response)

        if not result:
            raise NoDataEvent("Empty list returned")

        return result
