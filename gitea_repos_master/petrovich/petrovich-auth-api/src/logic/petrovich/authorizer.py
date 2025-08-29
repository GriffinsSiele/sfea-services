import logging

from isphere_exceptions.session import SessionBlocked
from pydash import get

from src.logic.adapter.frontend import FrontendAdapter
from src.logic.adapter.session import SessionAdapter
from src.logic.petrovich.proxy import proxy_cache_manager
from src.request_params.api.frontend import FrontendParams
from src.request_params.api.session import SessionParams


class Authorizer:
    def __init__(self, auth_data=None, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.cookies = get(auth_data, "cookies")
        self.proxy = None
        self.allowed_create_session = True

    async def _prepare(self):
        self.proxy = await proxy_cache_manager.get_proxy(
            query={"proxygroup": "1"},
            adapter="simple",
            fallback_query={"proxygroup": "1"},
            repeat=3,
        )
        logging.info(f"Using proxy {self.proxy}")

    async def create_new_session(self):
        if not self.allowed_create_session:
            raise SessionBlocked()

        self.allowed_create_session = False

        try:
            fp = FrontendParams(proxy=self.proxy)
            response = await fp.request()
            cookies, params = FrontendAdapter.parse(response)

            sp = SessionParams(proxy=self.proxy, cookies=cookies, params=params)
            response = await sp.request()
            self.cookies = SessionAdapter.parse(response)

        except Exception as e:
            logging.warning(e)

    def get_session(self):
        return {"cookies": self.cookies or {}}
