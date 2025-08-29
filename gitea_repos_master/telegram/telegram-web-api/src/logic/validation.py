import logging

from aiohttp import ClientHttpProxyError, ClientProxyConnectionError
from isphere_exceptions.proxy import ProxyLocked
from isphere_exceptions.session import SessionBlocked, SessionLocked
from isphere_exceptions.worker import UnknownError
from requests.exceptions import ProxyError
from telethon.errors import AuthKeyError, FloodError, TimedOutError, UnauthorizedError

from src.logic.proxy.proxy import ProxyCacheManager


class ResponseValidation:
    @staticmethod
    async def validate_request(request, custom_rules=None):
        try:
            response = await request.request()
        except (
            ClientProxyConnectionError,
            ClientHttpProxyError,
            ProxyError,
            TimeoutError,
            ConnectionError,
            TimedOutError,
        ) as e:
            logging.error(e)
            ProxyCacheManager.clear_cache()
            raise ProxyLocked(e)
        except FloodError as e:
            logging.error(e)
            raise SessionLocked(e)
        except (UnauthorizedError, AuthKeyError) as e:
            logging.error(e)
            raise SessionBlocked(e)
        except Exception as e:
            if custom_rules:
                for rule in custom_rules:
                    if isinstance(e, rule["name"]):
                        return rule["action"](e)

            raise UnknownError(f"Произошла ошибка отправки запроса: {e}")

        return response
