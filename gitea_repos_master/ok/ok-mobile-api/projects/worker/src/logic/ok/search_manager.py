import logging
from typing import Any

from isphere_exceptions.session import SessionEmpty, SessionLocked, SessionOutdated
from pydash import get
from worker_classes.logic.search_manager import SearchManager

from lib.src.logic.validation import ResponseValidation
from src.config.settings import PROXY_URL
from src.logic.adapters.profile import ProfileAdapter
from src.logic.ok.authorizer import AuthorizerOK
from src.logic.ok.profile import ProfileExtenderOKManager
from src.request_params.api.search_email import SearchEmailParams
from src.request_params.api.search_phone import SearchPhoneParams


class SearchOKManager(AuthorizerOK, SearchManager):
    def __init__(
        self,
        auth_data=None,
        use_proxy=True,
        rabbitmq=None,
        logger=logging,
        *args,
        **kwargs,
    ):
        super().__init__(auth_data, use_proxy, rabbitmq, logger, *args, **kwargs)

    async def _prepare(self):
        await self.set_proxy()
        self.can_update_session_key = True

    async def _search(self, payload: Any, *args, **kwargs):
        if not self.login:
            raise SessionEmpty()
        if not self.session_key:
            await self.authorize()

        payload = str(get(payload, "phone") or get(payload, "email") or payload)
        return await self._search_process(payload)

    async def _search_process(self, payload):
        payload_arg, func = self._payload_type(payload)
        sp = func(
            session_key=self.session_key,
            **payload_arg,
            proxy=self.proxy,
        )
        try:
            response = await ResponseValidation.validate_request(sp)
        except SessionOutdated:
            if self.can_update_session_key:
                self.logging.info("Session key is dead. Updating...")
                self.can_update_session_key = False
                await self.authorize()
                return await self._search_process(payload)
            else:
                raise SessionLocked(message="Исчерпан лимит попыток обновления")

        peom = ProfileExtenderOKManager(self.session_key, self.proxy, logger=self.logging)
        profile = await peom.extend(response)
        return ProfileAdapter.cast(profile)

    def _payload_type(self, payload):
        return (
            ({"phone_number": payload}, SearchPhoneParams)
            if "@" not in payload
            else ({"email": payload}, SearchEmailParams)
        )
