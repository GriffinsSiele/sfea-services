import asyncio
import logging
import time

from pydash import get

from lib.src.logic.validation import ResponseValidation
from src.request_params.api.group import GroupParams
from src.request_params.api.profile import ProfileParams


class AsyncProfileExtender:
    def __init__(
        self,
        session_key: str,
        uid: str,
        proxy=None,
        logger=logging,
    ):
        self.session_key = session_key
        self.uid = uid
        self.proxy = proxy
        self.logging = logger

    async def run(self):
        functions = [self._extend_profile, self._extend_group]
        return await asyncio.gather(*(self.wrapper(f) for f in functions))

    async def wrapper(self, function):
        start_time = time.time()
        self.logging.info(f"Start task: {function}")
        result = await function()
        end_time = time.time()
        self.logging.info(
            f"Task {function.__name__} ended. Elapsed time: {round(end_time - start_time, 2)} sec."
        )
        return result

    async def _extend_profile(self):
        self.logging.info(f"Extending profile uid={self.uid}")
        try:
            response = await ResponseValidation.validate_request(
                ProfileParams(self.session_key, self.uid, proxy=self.proxy)
            )

            user = get(response, "0.ok.user")

            return {
                **user,
                "counters": get(response, "1.ok.counters"),
                "access_levels": get(response, "2.ok.accessLevels"),
                "communities": get(response, "3.ok.groups"),
                "photos": get(response, "4.ok.photos"),
                "relatives": get(response, "5.ok.friends"),
            }
        except Exception as e:
            self.logging.info(f"Exception occurred while extending profile: {e}")
            return {}

    async def _extend_group(self):
        self.logging.info(f"Extending groups uid={self.uid}")
        try:
            response = await ResponseValidation.validate_request(
                GroupParams(self.session_key, self.uid, proxy=self.proxy)
            )
            return {"groups": get(response, "userGroups", [])}
        except Exception as e:
            self.logging.info(f"Exception occurred while extending group: {e}")
            return {}
