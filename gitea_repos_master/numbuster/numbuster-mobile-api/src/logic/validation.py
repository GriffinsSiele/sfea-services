import asyncio
import logging

from aiohttp import ClientHttpProxyError, ClientProxyConnectionError
from isphere_exceptions import ErrorNoReturnToQueue
from isphere_exceptions.proxy import ProxyLocked
from isphere_exceptions.session import (
    SessionBlocked,
    SessionInvalidCredentials,
    SessionLocked,
)
from isphere_exceptions.source import SourceIncorrectDataDetected, SourceTimeout
from isphere_exceptions.worker import UnknownError
from pydash import get
from requests.exceptions import ProxyError

from src.logic.proxy.proxy_cache import ProxyCacheManager


class NumbusterSourceTimeout(SourceTimeout):
    log_level = "warning"


class ResponseValidation:
    @staticmethod
    async def validate_request(request):
        try:
            response = await request.request()
        except (asyncio.exceptions.TimeoutError, TimeoutError) as e:
            logging.warning(f"Timeout error: {e}")
            ProxyCacheManager.clear_cache()
            raise SourceTimeout(e)
        except (
            ClientProxyConnectionError,
            ClientHttpProxyError,
            ProxyError,
            TimeoutError,
            ConnectionError,
        ) as e:
            ProxyCacheManager.clear_cache()
            raise ProxyLocked(e)
        except Exception as e:
            raise UnknownError(f"Произошла ошибка отправки запроса: {e}")

        if response.status_code == 401 or response.status_code == 405:
            raise ErrorNoReturnToQueue(
                "Нарушен процесс работы приложения, возможна изменилась подпись или иные параметры запроса"
            )

        if response.status_code == 426:
            raise SessionInvalidCredentials("Токен не валидный, не найден в системе")

        if response.status_code == 414 or "" == response.text:
            raise SessionBlocked("Аккаунт заблокирован")

        if response.status_code >= 500:
            raise ErrorNoReturnToQueue("Внутренняя ошибка источника")

        if response.text == "wrong phone":
            raise SourceIncorrectDataDetected("Некорректный номер телефона")

        try:
            response = response.json()
        except Exception:
            raise SessionLocked(f"Ответ сервера не json: {response.text}")

        status = get(response, "status", "")

        if status != "success":
            raise UnknownError(f"В ответе сообщение с ошибкой: {response}")

        return response
