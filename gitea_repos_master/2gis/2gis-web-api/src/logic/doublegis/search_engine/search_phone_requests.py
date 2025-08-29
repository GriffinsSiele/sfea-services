import asyncio
import logging

from isphere_exceptions.proxy import ProxyBlocked
from isphere_exceptions.source import SourceIncorrectDataDetected
from isphere_exceptions.worker import UnknownError
from pydash import get

from src.logic.adapters.region.phone_to_country import Country, CountryLocate
from src.logic.adapters.region.phone_to_region_phoneinfo import RegionLocatePhoneInfo
from src.logic.doublegis.search_engine.search_interface import (
    SearchTypeRequests2GISInterface,
)
from src.logic.doublegis.validation import ResponseValidation
from src.request_params.api.search_items import SearchItems


class SearchPhoneRequests2GIS(SearchTypeRequests2GISInterface):
    def __init__(self, cookies=None, extra_query=None, proxy=None):
        super().__init__(cookies, extra_query, proxy)

        self.country_mapper = {Country.RU: self._search_ru, Country.KZ: self._search_kz}

    async def search(self, phone):
        country = CountryLocate.locate(phone)

        search_extender = get(self.country_mapper, country)

        if not search_extender:
            raise SourceIncorrectDataDetected(
                "Не удалось определить страну. Поиск без страны невозможен."
            )

        si = SearchItems(proxy=self.proxy)
        search_extender(phone, si)
        si.cookies = self.cookies

        try:
            response = await si.request()
        except (ConnectionError, asyncio.TimeoutError) as e:
            raise ProxyBlocked(e)
        except Exception as e:
            logging.error(e)
            raise UnknownError(e)

        logging.info(
            f"Search for phone {phone} responded: {response}. {response.text[:300]}..."
        )
        response = ResponseValidation.validate_response(response)

        return await self.search_by_item(response)

    def _search_ru(self, phone, si: SearchItems):
        region = RegionLocatePhoneInfo().locate(phone)
        if not region:
            raise SourceIncorrectDataDetected(
                "Не удалось определить регион. Поиск без региона невозможен."
            )

        si.set_query(f"{phone} {region}", extra_query=self.extra_query)
        return si

    def _search_kz(self, phone, si: SearchItems):
        # Географический центр Казахстана
        viewpoint1 = [66.60777090499279, 61.77127692586553]
        viewpoint2 = [77.8535090950072, 34.92977307413447]

        si.set_query(
            phone,
            viewpoint1=viewpoint1,
            viewpoint2=viewpoint2,
            extra_query={**self.extra_query, "locale": "ru_KZ"},
        )
        return si
