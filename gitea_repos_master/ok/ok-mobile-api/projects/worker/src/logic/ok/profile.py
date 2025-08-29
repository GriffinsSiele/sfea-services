import logging

from isphere_exceptions.success import NoDataEvent
from pydash import get
from worker_classes.utils import short_str

from src.logic.ok.async_extender import AsyncProfileExtender


class ProfileExtenderOKManager:
    def __init__(self, session_key: str, proxy=None, logger=logging):
        self.session_key = session_key
        self.proxy = proxy
        self.logging = logger

    async def extend(self, response):
        user = get(response, "users.0")
        if not user:
            raise NoDataEvent()

        self.logging.info(f"Detected user: {short_str(user)}")

        extra_fields = await self._extend_profile(get(user, "uid"))
        return {**user, **extra_fields}

    async def _extend_profile(self, uid: str):
        responses = await AsyncProfileExtender(
            proxy=self.proxy, session_key=self.session_key, uid=uid, logger=self.logging
        ).run()

        return {k: v for d in responses for k, v in d.items()}
