import aiohttp
import asyncio
import logging
from typing import Callable

from app.managers.proxy import ProxyManager
from app.models import Proxy
from app.settings import LOGGER_NAME
from app.utils.database import DatabaseManager


PING_URLS = ["http://ifconfig.me/ip", "http://api.myip.com"]
SLEEP_TIMEOUT = 15
COUNT_TO_CHECK = 2

logger = logging.getLogger(LOGGER_NAME)


class ProxyPoll:
    @staticmethod
    async def __get_proxies() -> list:
        async with DatabaseManager.with_async_session() as session:
            proxy_manager = ProxyManager(session)
            return await proxy_manager.all()

    @staticmethod
    def __get_gather_tasks(
        lst: list[int], function: Callable, active: bool
    ) -> tuple[Callable, dict]:
        return function, {"where_conditions": [Proxy.id.in_(lst)], "active": active}

    @staticmethod
    async def __update_proxies(success_ids: list[int], reject_ids: list[int]):
        async with DatabaseManager.with_async_session() as session:
            proxy_manager = ProxyManager(session)
            tasks = []
            function = proxy_manager.update
            if len(success_ids):
                tasks.append(ProxyPoll.__get_gather_tasks(success_ids, function, True))
            if len(reject_ids):
                tasks.append(ProxyPoll.__get_gather_tasks(reject_ids, function, False))
            if len(tasks):
                await asyncio.gather(*[f(**kwargs) for f, kwargs in tasks])
                await proxy_manager.session.commit()

    @staticmethod
    async def __proxy_available(
        session: aiohttp.ClientSession, url: str, proxy_url: str
    ) -> bool:
        try:
            async with session.get(url, proxy=proxy_url) as response:
                return response.ok
        except Exception:
            return False

    @staticmethod
    async def __fetch(proxy: Proxy, url: str) -> tuple[int | None, int | None]:
        """
        Applies a request using a proxy. Returns a tuple (success_id, rejected_id).
        For the proxy with ID=1 this method returns (1, None) if the request is
        successful.
        For the proxy with ID=1 this method returns (None, 1) if the request is rejected.
        """
        timeout = aiohttp.ClientTimeout(total=5, sock_connect=1, sock_read=1)
        for _ in range(COUNT_TO_CHECK):
            async with aiohttp.ClientSession(timeout=timeout) as session:
                is_available = await ProxyPoll.__proxy_available(session, url, proxy.url)
                if is_available:
                    return proxy.id, None
                else:
                    continue
        return None, proxy.id

    @staticmethod
    def __parse_results(
        results: list[tuple[int | None, int | None]]
    ) -> tuple[list[int], list[int]]:
        rejected = []
        available = []
        for success_id, reject_id in results:
            available.append(success_id) if success_id else rejected.append(reject_id)
        return available, rejected

    @staticmethod
    async def __get_available_rejected(proxies: list[Proxy], url):
        results = await asyncio.gather(
            *(ProxyPoll.__fetch(proxy, url) for proxy in proxies)
        )
        return ProxyPoll.__parse_results(results)

    @staticmethod
    async def poll():
        proxies = await ProxyPoll.__get_proxies()
        proxies_length = len(proxies)
        available, rejected = [], []
        for url in PING_URLS:
            available, rejected = await ProxyPoll.__get_available_rejected(proxies, url)
            if len(rejected) != proxies_length:
                break
        if len(available) > 0:
            await ProxyPoll.__update_proxies(available, rejected)
            logger.info(f"Blocked proxies: {rejected}")
            logger.info(f"Available proxies: {available}")
        else:
            logger.warning(f"All proxies are unable to query urls: {PING_URLS}")
