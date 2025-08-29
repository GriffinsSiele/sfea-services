import json
import pathlib
import random

from isphere_exceptions.source import SourceOperationFailure
from proxy_manager import ProxyCacheManager
from putils_logic.putils import PUtils
from requests_logic.base import RequestBaseParamsAsync

from src.config.settings import PROXY_URL
from src.logger import logging

_current_file_path = pathlib.Path(__file__).parent.absolute()
proxy_cache_manager = ProxyCacheManager(
    proxy_url=PROXY_URL,
    cache_file=PUtils.bp(_current_file_path, "..", "..", "proxy_cache.json"),
    request_class=RequestBaseParamsAsync,
    rate_update=50,
)


list_screensize = [
    [1024, 768],
    [1152, 864],
    [1280, 768],
]


class BrowserOptions:
    def __init__(self) -> None:
        self.options: dict | None = None

    def load_from_file(self, filename: str | None = None) -> "BrowserOptions":
        if not filename:
            filename = "browser_options.json"
        conf_file = PUtils.bp(_current_file_path, "..", "config", filename)
        with open(conf_file, "r") as file:
            self.options = json.load(file)
        return self

    def set_random_screen_size(self) -> "BrowserOptions":
        if self.options:
            self.options["window_size"] = random.choice(list_screensize)
        return self

    async def set_proxy(self) -> "BrowserOptions":

        proxy = await proxy_cache_manager.get_proxy(
            query={"proxygroup": "1"}, repeat=3, fallback_query={"proxygroup": "1"}
        )
        if proxy and isinstance(proxy, dict):
            ip = proxy.get("server")
            try:
                port = proxy.get("port")
                port = int(port) if port else None
            except ValueError:
                return self
            user = proxy.get("login")
            password = proxy.get("password")
            if self.options and ip and port and user and password:
                self.options["proxy"] = [ip, port, user, password]
        return self

    def clear_proxy(self) -> None:
        pass

    def get_options(self) -> dict:
        if not self.options:
            logging.error("Browser options not provided.")
            raise SourceOperationFailure()
        logging.info(f"Browser options: {self.options}")
        return self.options
