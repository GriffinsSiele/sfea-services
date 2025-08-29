import logging
from typing import Any

from isphere_exceptions.session import SessionBlocked, SessionEmpty
from isphere_exceptions.success import NoDataEvent
from worker_classes.logic.search_manager import SearchManager

from src.logic.telegram.authorizer import Authorizer
from src.logic.telegram.extend_profile import ExtendProfile
from src.logic.validation import ResponseValidation
from src.request_params.api.telegram_contacts import TelegramContactsParams
from src.request_params.api.telegram_delete import TelegramDeleteParams
from src.request_params.api.telegram_get import TelegramGetParams
from src.request_params.api.telegram_import import TelegramImportParams


class SearchTelegramManager(Authorizer, SearchManager):
    def __init__(self, auth_data=None, logging=logging, *args, **kwargs):
        super().__init__(auth_data=auth_data, logger=logging, *args, **kwargs)
        self.logging = logging

    async def _search(self, payload: Any):
        await self._prepare_state()

        if not self.auth_key:
            raise SessionEmpty()

        payload = str(
            payload.get(payload, "phone") or payload.get(payload, "nick")
            if isinstance(payload, dict)
            else payload
        )

        is_phone = payload.isdigit()
        func = self.search_by_phone if is_phone else self.search_by_nickname
        return await func(payload)

    async def search_by_phone(self, payload):
        tip = TelegramImportParams(self.client, payload)
        response = await ResponseValidation.validate_request(tip)
        self.logging.info(f"Response: {response}")

        if response.retry_contacts:
            raise SessionBlocked(
                f"Account blocked due to retry_contacts is not empty: {response.retry_contacts}"
            )
        if response.popular_invites or not response.users:
            raise NoDataEvent("Results not found")

        return await ExtendProfile(self.client, proxy=self.proxy).extend_users(
            response.users
        )

    async def search_by_nickname(self, payload):
        tcp = TelegramContactsParams(self.client)
        contacts = await ResponseValidation.validate_request(tcp)
        self.logging.info(f"Deleting {len(contacts.users)} contacts from imported...")

        if contacts:
            tdp = TelegramDeleteParams(self.client, [c.id for c in contacts.users])
            status = await ResponseValidation.validate_request(tdp)
            self.logging.info(f"Deletion status: {status}")

        def no_data_error(e):
            self.logging.error(e)
            raise NoDataEvent(e)

        tgp = TelegramGetParams(self.client, payload)
        response = await ResponseValidation.validate_request(
            tgp, custom_rules=[{"name": ValueError, "action": no_data_error}]
        )
        self.logging.info(f"Response: {response}")
        return await ExtendProfile(self.client, proxy=self.proxy).extend_users(
            [response], is_full_user=True
        )

    async def close_session(self):
        if self.client and self.client.is_connected():
            await self.client.disconnect()
