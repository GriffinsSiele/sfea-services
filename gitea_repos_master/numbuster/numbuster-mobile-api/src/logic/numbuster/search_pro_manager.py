import logging
from typing import Union

from isphere_exceptions.session import SessionEmpty
from pydash import get, unset

from src.logic.adapters.profile import ProfileAdapter
from src.logic.numbuster.search_manager_interface import SearchNumbusterManagerInterface
from src.logic.validation import ResponseValidation
from src.request_params.api.v202204.contacts import ContactsParams
from src.utils.utils import get_closest_3_am


class SearchNumbusterProManager(SearchNumbusterManagerInterface):
    def __init__(self, auth_data=None, proxy=None, logger=logging, *args, **kwargs):
        super().__init__(auth_data, proxy, logger=logger, *args, **kwargs)
        self.locked_search_method = False

    async def _search(self, payload: Union[str, dict], *args, **kwargs):
        if not self.access_token:
            raise SessionEmpty()
        responses = await self._main_process(self._incoming_call, payload)
        response = self._parse_responses(responses)
        self._validate_profile(response)

        response = await self._search_call(payload)
        response = self._parse_responses([response])
        self.__limit_statistic(response)
        contacts = await self.__extend_names(payload, response)
        return contacts

    def __limit_statistic(self, response):
        count = get(response, "0.__left_request")
        if count is None:
            return

        self.logging.info(f"Search method requests left: {count}")
        if count <= 0:
            _, self.locked_search_method = get_closest_3_am()
        unset(response, "0.__left_request")

    async def __extend_names(self, payload, response):
        names_count = get(response, "0.names_count")
        if not (names_count and names_count >= 1):
            return []

        try:
            response = await ResponseValidation.validate_request(
                ContactsParams(
                    phone_number=payload,
                    access_token=self.access_token,
                    domain=self.host,
                    proxy=self.proxy,
                )
            )
            self.logging.info(f"Response: {response}")
            return ProfileAdapter.cast_contacts(response)
        except Exception as e:
            self.logging.error(e)
            return []

    @property
    def next_use(self):
        return (
            {"seconds": self.locked_search_method} if self.locked_search_method else None
        )
