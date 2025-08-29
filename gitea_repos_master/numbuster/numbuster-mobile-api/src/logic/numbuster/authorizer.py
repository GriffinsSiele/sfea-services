import asyncio
import logging
import pathlib
from datetime import datetime, timedelta

from proxy_manager import ProxyCacheManager
from putils_logic.putils import PUtils
from pydash import get
from requests_logic.base import RequestBaseParamsAsync

from src.config.settings import PROXY_URL
from src.logic.numbuster.host_manager import HostManager
from src.utils.utils import timing

_current_file_path = pathlib.Path(__file__).parent.absolute()
proxy_cache_manager = ProxyCacheManager(
    proxy_url=PROXY_URL,
    cache_file=PUtils.bp(_current_file_path, "..", "..", "..", "proxy_cache.json"),
    request_class=RequestBaseParamsAsync,
    rate_update=50,
)


class Authorizer:
    def __init__(self, auth_data=None, proxy=None, logger=logging, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.logging = logger

        self.host = get(auth_data, "host")
        self.host_updated = self.__cast_datetime(get(auth_data, "host_updated"))

        self.access_token = get(auth_data, "access_token")
        self.fcm_token = get(auth_data, "fcm_token")

        self.meta = get(auth_data, "meta")

        self.proxy = get(auth_data, "proxy_id") if proxy is None else None

    def __cast_datetime(self, dt):
        return datetime.strptime(dt, "%Y-%m-%d %H:%M:%S.%f") if dt else None

    async def _prepare_host_proxy(self):
        await asyncio.gather(*[self._prepare_host(), self._prepare_proxy()])

    @timing
    async def _prepare_host(self):
        if (
            not self.host_updated
            or self.host_updated + timedelta(hours=6) < datetime.now()
        ):
            self.logging.info("Host for session is outdated. Updating...")
            self.host = await HostManager().resolve()
            self.host_updated = datetime.now()
        self.logging.info(f"Set host: {self.host}")

    @timing
    async def _prepare_proxy(self):
        self.proxy = (
            await proxy_cache_manager.get_proxy(
                query={"id": self.proxy},
                fallback_query={"proxygroup": 1},
                repeat=3,
                adapter="simple",
            )
            if self.proxy
            else None
        )

    def get_session(self):
        return {
            "access_token": self.access_token,
            "fcm_token": self.fcm_token,
            "host": self.host,
            "host_updated": str(self.host_updated) if self.host_updated else None,
            "proxy_id": get(self.proxy, "extra_fields.id"),
            "meta": self.meta,
        }
