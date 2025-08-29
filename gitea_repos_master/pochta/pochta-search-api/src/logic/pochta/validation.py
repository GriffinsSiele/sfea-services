import logging
import pathlib
from asyncio.exceptions import TimeoutError as TE

from aiohttp import ClientHttpProxyError, ClientProxyConnectionError
from isphere_exceptions.proxy import ProxyBlocked
from isphere_exceptions.session import SessionBlocked
from isphere_exceptions.source import SourceParseError
from isphere_exceptions.worker import UnknownError
from urllib3.exceptions import ProxyError

from src.request_params.interfaces.base import RequestParams

_current_file_path = pathlib.Path(__file__).parent.absolute()


class ResponseValidation:
    @staticmethod
    async def validate_response(rp: RequestParams):
        try:
            response = await rp.request()
        except (
            ClientProxyConnectionError,
            ClientHttpProxyError,
            ProxyError,
            TimeoutError,
            TE,
            ConnectionError,
        ) as e:
            raise ProxyBlocked(str(e))
        except Exception as e:
            raise UnknownError(e)

        logging.info(f"Response [{response.status_code}]: {response.text}")
        if response.status_code == 401:
            raise SessionBlocked()

        if response.status_code != 200:
            raise SourceParseError()

        try:
            data = response.json()
        except Exception as e:
            raise SourceParseError("Ответ сервера не json")

        if "outputAddressSuggestions" not in response.text:
            raise SourceParseError()

        return data
