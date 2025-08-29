import datetime
import logging

from isphere_exceptions.session import SessionBlocked
from pydash import get

from src.config.settings import MAX_SEARCH_PER_DAY
from src.logic.proxy.proxy import ProxyCacheManager, cast_proxy, proxy_cache_manager
from src.logic.validation import ResponseValidation
from src.request_params.api.telegram_auth import TelegramAuth


class Authorizer:
    def __init__(self, auth_data=None, logger=logging):
        self.auth_key = get(auth_data, "auth_key")
        self.api_id = get(auth_data, "api_id")
        self.api_hash = get(auth_data, "api_hash")
        self.password = get(auth_data, "password")
        self.proxy_id = get(auth_data, "proxy_id")
        self.last_message = get(auth_data, "last_message")
        self.friends = get(auth_data, "friends")

        self.logging = logger
        self.client = None

    async def _prepare_state(self):
        await self.__prepare_proxy()
        await self.__prepare_session()

    async def __prepare_proxy(self):
        self.proxy = (
            await proxy_cache_manager.get_proxy(
                query={"id": self.proxy_id}, fallback_query={"proxygroup": "5"}, repeat=3
            )
            if self.proxy_id
            else None
        )
        self.proxy = cast_proxy(self.proxy)

        proxy_id = get(self.proxy, "id")
        if proxy_id:
            self.proxy_id = proxy_id
            del self.proxy["id"]
        else:
            self.proxy_id = None
        self.logging.info(f"Using proxy: {self.proxy}")

    async def __prepare_session(self):
        session_creator = TelegramAuth(
            auth_key=self.auth_key,
            api_id=self.api_id,
            api_hash=self.api_hash,
            proxy=self.proxy,
        )

        def account_blocked(e):
            self.logging.error(e)
            raise SessionBlocked(e)

        self.client = await ResponseValidation.validate_request(
            session_creator,
            custom_rules=[{"name": ValueError, "action": account_blocked}],
        )

    def calc_next_use(self):
        next_use_delay = 24 * 60 * 60 / MAX_SEARCH_PER_DAY
        return datetime.datetime.now() + datetime.timedelta(seconds=next_use_delay)

    def get_session(self):
        return {
            "auth_key": self.auth_key,
            "api_id": self.api_id,
            "api_hash": self.api_hash,
            "password": self.password,
            "proxy_id": self.proxy_id,
            "last_message": self.last_message,
            "friends": self.friends or [],
        }
