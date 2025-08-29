import aiohttp
from isphere_exceptions.source import SourceError

from src.logic.elpts.base_elpts import BaseElPts
from src.logic.page_parsers import (
    CaptchaResponseParser,
    MainPageParser,
    PostResponseParser,
)


class ElPtsVin(BaseElPts):
    main_page_parser = MainPageParser()
    post_response_parser = PostResponseParser()
    captcha_response_parser = CaptchaResponseParser()

    async def search(self, data: str, *args, **kwargs) -> dict:
        try:
            search_result = await super().search(data)
        except (
            aiohttp.client_exceptions.TooManyRedirects,
            aiohttp.client_exceptions.InvalidURL,
            SourceError,
        ):
            self.clean()
            await self.prepare()
            search_result = await super().search(data)

        if not search_result:
            raise SourceError()

        return search_result
