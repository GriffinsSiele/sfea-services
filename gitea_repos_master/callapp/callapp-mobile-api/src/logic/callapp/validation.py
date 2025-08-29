import asyncio
import logging

from aiohttp import ClientHttpProxyError, ClientProxyConnectionError
from isphere_exceptions.proxy import ProxyBlocked
from isphere_exceptions.session import SessionLimitError
from isphere_exceptions.source import SourceConnection
from isphere_exceptions.success import NoDataEvent
from isphere_exceptions.worker import UnknownError
from requests.exceptions import ProxyError


class ResponseValidation:
    @staticmethod
    async def validate_response(request):
        try:
            response = await request.request()
        except (
            ClientProxyConnectionError,
            ClientHttpProxyError,
            ProxyError,
            TimeoutError,
            asyncio.exceptions.TimeoutError,
            ConnectionError,
        ) as e:
            raise ProxyBlocked(e)
        except Exception as e:
            raise UnknownError(f"Ошибка в запросе: {e}")

        if response.status_code == 525:
            raise NoDataEvent("User not found")

        if response.status_code == 513:
            raise SessionLimitError("Possible rate limit")

        if response.status_code == 510 or response.status_code == 503:
            raise SourceConnection("Источник не отвечает на запросы")

        if response.status_code != 200:
            raise UnknownError(f"Code: {response.status_code}. Text: {response.text}")

        if response.text == "null":
            raise NoDataEvent("User not found")

        try:
            response = response.json()
        except Exception as e:
            raise UnknownError(f"Error in parse json: {e}. Text: {response.text}")

        logging.info(f"Response is valid: {str(response)}")
        return response
