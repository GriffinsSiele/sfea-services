from requests_logic.base import RequestBaseParamsAsync

from src.logger.context_logger import logging
from src.proxy import proxy_cache_manager


class Client:
    USE_PROXY = True
    PROXY_GROUP = "1"
    PROXY_GROUP_FALLBACK = "5"
    REQUEST_CLASS = RequestBaseParamsAsync

    DEFAULT_ARGS = {"timeout": 10, "verify": False}

    def __init__(
        self,
        request_class,
        use_proxy=None,
        proxy_group=None,
        proxy_group_fallback=None,
        *args,
        **kwargs,
    ):
        self.REQUEST_CLASS = request_class or self.REQUEST_CLASS
        self.PROXY_GROUP = proxy_group or self.PROXY_GROUP_FALLBACK
        self.PROXY_GROUP_FALLBACK = proxy_group_fallback or self.PROXY_GROUP_FALLBACK
        self.USE_PROXY = self.USE_PROXY if use_proxy is not None else use_proxy

        self.args = args
        self.kwargs = kwargs

    async def __get_proxy(self):
        if not self.USE_PROXY:
            return None

        return await proxy_cache_manager.get_proxy(
            query={"proxygroup": self.PROXY_GROUP},
            fallback_query={"proxygroup": self.PROXY_GROUP_FALLBACK},
            repeat=3,
        )

    async def request(self, url, *args, **kwargs):
        proxy = await self.__get_proxy()
        logging.info(f"Using proxy: {proxy}")

        def log(*args, **kwargs):
            logging.info(args, kwargs)

        # log(url=url, proxy=proxy, *args, *self.args, **self.DEFAULT_ARGS, **self.kwargs, **kwargs)
        client = self.REQUEST_CLASS(
            url=url,
            proxy=proxy,
            *args,
            *self.args,
            **self.DEFAULT_ARGS,
            **self.kwargs,
            **kwargs,
        )
        return await client.request()

    async def post(self, url, *args, **kwargs):
        return await self.request(url=url, method="POST", *args, **kwargs)

    async def get(self, url, *args, **kwargs):
        return await self.request(url=url, method="GET", *args, **kwargs)

    async def put(self, url, *args, **kwargs):
        return await self.request(url=url, method="PUT", *args, **kwargs)

    async def head(self, url, *args, **kwargs):
        return await self.request(url=url, method="HEAD", *args, **kwargs)
