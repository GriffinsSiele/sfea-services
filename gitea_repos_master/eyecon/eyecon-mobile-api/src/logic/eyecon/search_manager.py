import asyncio
import logging
import pathlib

from isphere_exceptions.session import SessionEmpty
from isphere_exceptions.source import SourceIncorrectDataDetected
from worker_classes.logic.search_manager import SearchManager

from src.logic.adapter.response import ResponseAdapter
from src.logic.eyecon.authorizer import Authorizer
from src.logic.eyecon.validation import ResponseValidation
from src.request_params.api.picture_content import PictureContentParams
from src.request_params.api.picture_link import PictureLinkParams
from src.request_params.api.search import SearchParams

_current_file_path = pathlib.Path(__file__).parent.absolute()


class SearchEyeconManager(Authorizer, SearchManager):
    def __init__(self, auth_data=None, *args, **kwargs):
        super().__init__(auth_data=auth_data, *args, **kwargs)

    async def _search(self, payload, redelivered=False, *args, **kwargs):
        payload = str(payload.get("phone", "") if isinstance(payload, dict) else payload)
        try:
            if not self.e_auth:
                raise SessionEmpty()

            if not self.prepared:
                await self._prepare()

            responses = await asyncio.gather(
                *[self._search_content(payload), self._search_image(payload)]
            )
            return ResponseAdapter.merge(responses)
        except Exception as e:
            if redelivered:
                raise SourceIncorrectDataDetected()
            raise e

    async def _search_content(self, payload):
        search_params = SearchParams(
            phone=payload,
            e_auth=self.e_auth,
            e_auth_c=self.e_auth_c,
            proxy=self.proxy,
        )
        response = await ResponseValidation.validate_response(search_params)
        return ResponseAdapter.cast(response)

    async def _search_image(self, payload):
        picture_params = PictureLinkParams(
            phone=payload, e_auth=self.e_auth, e_auth_c=self.e_auth_c, proxy=self.proxy
        )
        try:
            response = await picture_params.request()
        except Exception as e:
            logging.warning(e)
            return None

        if response.status_code == 404:
            return None

        location_url = response.headers.get("Location")
        if not location_url:
            logging.warning(
                f"No location URL in response for picture: {response} {response.text}"
            )
            return None

        picture_params = PictureContentParams(
            url=location_url, proxy=self.proxy, timeout=10
        )
        try:
            response = await picture_params.request()
        except Exception as e:
            logging.error(e)
            return None

        if len(response.content) > 100:
            return ResponseAdapter.image(response.content)

        logging.error(f"Unknown response: {response.content}")
        return None
