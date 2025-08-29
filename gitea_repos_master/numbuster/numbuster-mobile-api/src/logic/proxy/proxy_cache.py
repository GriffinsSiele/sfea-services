import json
import logging
import os
import pathlib

from putils_logic.putils import PUtils
from pydash import find, get
from requests_logic.proxy import ProxyManager

from src.config.settings import PROXY_URL

_current_file_path = pathlib.Path(__file__).parent.absolute()


class ProxyCacheManager:
    PROXY_CONFIG = PUtils.bp(_current_file_path, "..", "..", "..", "proxy_cache.json")

    @staticmethod
    def __read_cache():
        if not PUtils.is_file_exists(ProxyCacheManager.PROXY_CONFIG):
            return []

        with open(ProxyCacheManager.PROXY_CONFIG, "r") as f:
            return json.load(f)

    @staticmethod
    def __write_cache(data):
        with open(ProxyCacheManager.PROXY_CONFIG, "w") as f:
            f.write(json.dumps(data, indent=4))

    @staticmethod
    def clear_cache():
        if PUtils.is_file_exists(ProxyCacheManager.PROXY_CONFIG):
            os.remove(ProxyCacheManager.PROXY_CONFIG)

    @staticmethod
    async def __request_proxy(query, *args, **kwargs):
        if not query:
            return None

        proxy = await ProxyManager(PROXY_URL).get_proxy(query, *args, **kwargs)

        data = ProxyCacheManager.__read_cache()
        data.append(proxy)
        ProxyCacheManager.__write_cache(data)

        return proxy

    @staticmethod
    async def get_proxy(id_, *args, **kwargs):
        if not id_:
            return None

        data = ProxyCacheManager.__read_cache()
        found = find(data, lambda x: get(x, "extra_fields.id") == id_)
        if found:
            return ProxyCacheManager._cast(found)

        proxy = await ProxyCacheManager.__request_proxy({"id": id_}, *args, **kwargs)
        return ProxyCacheManager._cast(proxy)

    @staticmethod
    def _cast(proxy):
        logging.info(f"Using proxy: {proxy}")
        return proxy
