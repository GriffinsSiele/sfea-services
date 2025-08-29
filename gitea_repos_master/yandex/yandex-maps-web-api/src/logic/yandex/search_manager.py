import logging

from isphere_exceptions.session import SessionBlocked, SessionLocked
from pydash import get
from worker_classes.logic.search_manager import SearchManager

from src.logic.yandex.authorizer import AuthorizerYandexMaps
from src.logic.yandex.search_engine.search_phone_requests import SearchPhoneRequests


class SearchYandexMapsManager(AuthorizerYandexMaps, SearchManager):
    def __init__(self, auth_data=None, proxy=None, *args, **kwargs):
        self.logging = logging
        super().__init__(auth_data, proxy, *args, **kwargs)

        self.retry_auth_allowed = True

    async def _prepare(self):
        self.retry_auth_allowed = True
        await self._prepare_proxy()

    async def _search(self, payload, *args, **kwargs):
        payload = str(
            get(payload, "phone")
            or get(payload, "email")
            or get(payload, "url")
            or payload
        )
        result = await self.__search(payload)
        return result

    async def __search(self, payload):
        if not self.is_authed_session():
            logging.info("Not authed. Trying auth.")
            await self.set_auth()

        try:
            return await self._search_by_phone(payload)
        except SessionLocked as e:
            if not self.retry_auth_allowed:
                raise SessionBlocked("Cannot update session")

            logging.info("Trying update session")
            self.retry_auth_allowed = False
            self.csrf_token = str(e).split(" ")[-1]

            return await self.__search(payload)

    async def _search_by_phone(self, payload):
        sl = SearchPhoneRequests(
            cookies=self.cookies,
            session_id=self.session_id,
            csrf_token=self.csrf_token,
            proxy=self.proxy,
        )
        return await sl.search(payload)
