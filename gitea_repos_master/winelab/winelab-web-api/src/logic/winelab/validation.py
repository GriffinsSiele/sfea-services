import logging
import pathlib
from asyncio.exceptions import TimeoutError as TE

from aiohttp import ClientHttpProxyError, ClientProxyConnectionError
from isphere_exceptions.proxy import ProxyBlocked
from isphere_exceptions.session import SessionBlocked
from isphere_exceptions.worker import UnknownError
from urllib3.exceptions import ProxyError

from src.request_params.interfaces.base import RequestParams

_current_file_path = pathlib.Path(__file__).parent.absolute()


class ResponseValidation:
    @staticmethod
    async def validate_response(rp: RequestParams, format="html"):
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

        if format != "html" and "Добро пожаловать в магазин алкоголя" in response.text:
            raise SessionBlocked()

        response_text = str(response.text).replace("\n", "")[:200]
        logging.info(f"Response is valid: {response}: {response_text}")
        return response
