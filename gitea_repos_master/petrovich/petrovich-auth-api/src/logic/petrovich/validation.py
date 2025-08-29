import logging
import pathlib
from asyncio.exceptions import TimeoutError as TE

from aiohttp import ClientHttpProxyError, ClientProxyConnectionError
from isphere_exceptions.proxy import ProxyBlocked
from isphere_exceptions.session import SessionBlocked
from isphere_exceptions.source import (
    SourceOperationFailure,
    SourceParseError,
    SourceIncorrectDataDetected,
)
from isphere_exceptions.worker import UnknownError
from pydash import get
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

        short_response = response.text[:100].strip().replace("\n", " ")
        logging.info(f'Response: {short_response}')

        if (
            "Session is broken" in response.text
            or "Session is not started" in response.text
        ):
            raise SessionBlocked()

        if "Некорректный" in response.text:
            raise SourceIncorrectDataDetected()

        if "Запрос успешно выполнен" not in response.text:
            raise SourceParseError()

        try:
            data = response.json()
        except Exception as e:
            raise SourceParseError(f"Ответ сервера не json: {response}: {response.text}")

        code = get(data, "state.code")
        if code != 200:
            raise SourceOperationFailure(f"Неизвестный код ответа: {data}")

        return data
