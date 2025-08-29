import logging

from isphere_exceptions.worker import UnknownError
from pydash import get

from src.logic.adapters.session import AuthAdapter
from src.logic.proxy.proxy import ProxyCacheManager, proxy_cache_manager
from src.request_params.api.auth import AuthParams


class AuthorizerYandexMaps:
    def __init__(self, auth_data=None, proxy=None, *args, **kwargs):
        self.cookies = get(auth_data, "cookies", {})
        self.proxy_params = proxy
        self.session_id = get(auth_data, "session_id")
        self.csrf_token = get(auth_data, "csrf_token")

    async def _prepare_proxy(self):
        self.proxy = (
            await proxy_cache_manager.get_proxy(
                query={"proxygroup": "1"},
                fallback_query={"proxygroup": "1"},
                repeat=3,
                adapter="simple",
            )
            if self.proxy_params
            else None
        )
        logging.info(f"Using proxy: {self.proxy}")

    def is_authed_session(self):
        return self.csrf_token and self.session_id

    async def set_auth(self):
        try:
            auth = AuthParams(proxy=self.proxy)
            response = await auth.request()
        except Exception as e:
            logging.error(f"Exception in auth: {e}")
            raise UnknownError(e)

        session = AuthAdapter.cast(response)
        logging.info(f"New session: {session}")

        self.cookies = session.get("cookies")
        self.session_id = session.get("session_id")
        self.csrf_token = session.get("csrf_token")

    def get_session(self):
        return {
            "cookies": self.cookies,
            "session_id": self.session_id,
            "csrf_token": self.csrf_token,
        }
