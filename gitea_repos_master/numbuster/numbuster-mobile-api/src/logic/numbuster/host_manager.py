import logging
import random

from isphere_exceptions import ErrorNoReturnToQueue
from pydash import filter_, find, find_index, get

from src.config.app import ConfigApp
from src.logic.validation import ResponseValidation
from src.request_params.api.v6.host_resolve import ResolveHostParams
from src.utils.meta import SingleInstanceMetaClass


class HostManager(metaclass=SingleInstanceMetaClass):
    known_hosts = [
        {
            "url": ConfigApp.BASE_HOST,
            "ban_ru": False,
            "active": True,
        },
        {
            "url": "https://bankrusianame.com",
            "ban_ru": False,
            "active": True,
        },
        {
            "url": "https://7loop.name",
            "ban_ru": True,
            "active": True,
        },
        {
            "url": "https://callerlocator.de",
            "ban_ru": True,
            "active": True,
        },
        {
            "url": "https://a7bdece4963d6d4999981d84bcc14582b3cc880e72b52.cc",
            "ban_ru": True,
            "active": True,
        },
        {
            "url": "https://cbb5723fed9575909f113b17c09bc4bb3b6466c.de",
            "ban_ru": True,
            "active": True,
        },
        {
            "url": "https://b0e6cc97e9a9170e0234ce5912e24a4cb8ff9090737a645e29.de",
            "ban_ru": True,
            "active": True,
        },
        {
            "url": "https://a7bdece4963d6d4969981d84bcc14582b3cc880e72b52.cc",
            "ban_ru": True,
            "active": True,
        },
        {
            "url": "http://7d7992e49365310ec5e997241c6312bd.com",
            "ban_ru": False,
            "active": True,
        },
        {
            "url": "https://7d7992e49365310ec5e997241c6312bd.com",
            "ban_ru": True,
            "active": True,
        },
        {
            "url": "http://api.numbuster.com",
            "ban_ru": False,
            "active": True,
        },
        {
            "url": "https://b0e6cc97e9a9170e0234ce5912e24a4cb8ff9090737a645e29.de",
            "ban_ru": True,
            "active": True,
        },
        {
            "url": "http://96969696969696696666999696969999696969.com",
            "ban_ru": False,
            "active": True,
        },
        {
            "url": "https://96969696969696696666999696969999696969.com",
            "ban_ru": True,
            "active": True,
        },
        {
            "url": "http://969696969696966966669999696969999696969.com",
            "ban_ru": False,
            "active": True,
        },
        {
            "url": "http://969696969696966966669999696969999696969.com",
            "ban_ru": True,
            "active": True,
        },
        {
            "url": "https://96969696969696696696699996969699996969699.com",
            "ban_ru": True,
            "active": True,
        },
        {
            "url": "https://apikz.nmb.st",
            "ban_ru": True,
            "active": True,
        },
        {
            "url": "https://bankrossia.com",
            "ban_ru": True,
            "active": True,
        },
        {
            "url": "https://cbb5723fed9575909f113b17c09bc4bb3d6466c.de",
            "ban_ru": True,
            "active": True,
        },
        {
            "url": "https://969696969696966966669999696969999696969.com",
            "ban_ru": True,
            "active": True,
        },
    ]

    def __init__(self, logger=logging):
        self.logging = logger

    def disable_host(self, url):
        index = find_index(self.known_hosts, lambda h: h["url"] == url)
        if index >= 0:
            self.known_hosts[index]["active"] = False

    async def resolve(self):
        rhm = ResolveHostParams()

        response = None
        try:
            response = await ResponseValidation.validate_request(rhm)
        except Exception as e:
            logging.warning(e)

        url = get(response, "data")
        return await self.__pick_url(url)

    def __random(self):
        host = random.choice(filter_(self.known_hosts, self.__is_valid))
        if not host:
            raise ErrorNoReturnToQueue(
                "Все известные домены Numbuster заблокированы в РФ"
            )
        return host["url"]

    def __is_valid(self, host):
        return host["active"] and not host["ban_ru"] and host["url"].startswith("https")

    async def __pick_url(self, url):
        if not url:
            return self.__random()

        existed = find(self.known_hosts, lambda h: h["url"] == url)
        if existed and self.__is_valid(existed):
            return existed["url"]
        elif existed:
            return self.__random()

        try:
            await ResponseValidation.validate_request(ResolveHostParams(domain=url))
        except Exception as e:
            logging.error(e)
            self.known_hosts.append({"url": url, "active": False, "ban_ru": True})
            update_url = True
        else:
            self.known_hosts.append({"url": url, "active": True, "ban_ru": False})
            update_url = False

        self.logging.info(f"Detected new domain: {url}")
        return self.__random() if update_url else url
