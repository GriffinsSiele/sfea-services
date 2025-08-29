import asyncio
import logging
from typing import Any

from aiohttp import client_exceptions
from isphere_exceptions.worker import InternalWorkerError
from requests_logic.base import RequestBaseParamsAsync

from src.logger import context_logging


class BaseAsyncRequestClient(RequestBaseParamsAsync):

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.timeout = 5
        self.verify = False

    async def request(self, *args, **kwargs) -> dict[str, Any]:
        logging.info(f'BaseAsyncRequestClient(args={args}, kwargs={kwargs}, request_args={self._request_args()})', )
        try:
            response = await super().request(*args, **kwargs)
            return response.json()
        except asyncio.TimeoutError:
            err_msg = "Connection timeout error."
        except (client_exceptions.ClientError, Exception) as exc:
            err_msg = f"{exc.message if hasattr(exc, 'message') else exc.__str__()}."
        context_logging.warning(err_msg)
        raise InternalWorkerError(message=f"Возникла ошибка: {err_msg}")
