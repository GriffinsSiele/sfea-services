import logging
import pathlib
from datetime import datetime, timedelta

from proxy_manager import ProxyCacheManager
from putils_logic.putils import PUtils
from pydash import get
from requests_logic.base import RequestBaseParamsAsync

from lib.src.config.app import ConfigApp
from lib.src.logic.device.generator import DeviceGenerator
from src.config.settings import PROXY_URL

_current_file_path = pathlib.Path(__file__).parent.absolute()
proxy_cache_manager = ProxyCacheManager(
    proxy_url=PROXY_URL,
    cache_file=PUtils.bp(_current_file_path, "..", "..", "..", "proxy_cache.json"),
    request_class=RequestBaseParamsAsync,
    rate_update=50,
)


class BaseAuthorizer:
    def __init__(self, auth_data=None, use_proxy=True, logger=logging, *args, **kwargs):
        self.login = get(auth_data, "login")
        self.password = get(auth_data, "password")
        self.session_key = get(auth_data, "session_key")
        self.device = get(auth_data, "device_id", DeviceGenerator.generate())
        self.cookies = get(auth_data, "cookies", {})
        self.limit_usage = get(auth_data, "limit_usage", [])

        self.auth_data = auth_data

        self.proxy = None
        self.use_proxy = use_proxy

        self.logging = logger

    async def set_proxy(self):
        if self.use_proxy:
            proxy_id = {"id": get(self.auth_data, "proxy_id") or "-1"}
            self.logging.info(f"Looking for proxy: {proxy_id}")
            self.proxy = await proxy_cache_manager.get_proxy(
                {"id": proxy_id},
                fallback_query={"proxygroup": "1"},
                repeat=3,
                adapter="simple",
            )

        self.logging.info(f"Using proxy: {self.proxy}")

    def get_session(self):
        proxy_id = get(self.auth_data, "proxy_id") or get(self.proxy, "extra_fields.id")
        return {
            "login": self.login,
            "password": self.password,
            "session_key": self.session_key,
            "proxy_id": proxy_id or "-1",
            "device_id": str(self.device),
            "cookies": self.cookies,
            "limit_usage": self.limit_usage,
        }

    def next_use(self):
        if len(self.limit_usage) != ConfigApp.MAX_SESSION_USE:
            self.__solve_changing_in_limits()

        del self.limit_usage[0]
        self.limit_usage.append(datetime.now() + timedelta(minutes=1))
        return self.limit_usage[0] + timedelta(hours=24)

    def __solve_changing_in_limits(self, offset=24):
        limit = len(self.limit_usage)
        usages = datetime.now() - timedelta(hours=offset)
        if limit < ConfigApp.MAX_SESSION_USE:
            for _ in range(ConfigApp.MAX_SESSION_USE - limit):
                self.limit_usage.append(usages)
        else:
            self.limit_usage = self.limit_usage[: ConfigApp.MAX_SESSION_USE]

    def set_temp_lock(self):
        self.limit_usage = []
        self.__solve_changing_in_limits(0)
