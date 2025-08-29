from typing import Type

from asgiref.sync import sync_to_async
from pydash import get
from requests import Response

from src.interfaces import AbstractResultParser, AbstractSamsung, AbstractSamsungSource
from src.logger.context_logger import logging
from src.utils import ExtStr
from src.utils.utils import informer


class SamsungCommon(AbstractSamsung):
    """Осуществляет поиск аккаунта на сайте Samsung."""

    samsung_source: Type[AbstractSamsungSource]
    search_parser: Type[AbstractResultParser]

    def __init__(self, session: dict, proxy: dict) -> None:
        """Метод инициализации класса.

        :param session: извлечённая из базы данных сессия (заголовки, куки, id прокси, ...)
        :param proxy: прокси, которая указана в извлеченной сессии (или рандомная прокси)
        """
        super().__init__()
        self.session = session
        self.proxy = proxy

    async def search(self, data: str | dict) -> dict:
        """Запускает поиск аккаунта на сайте Samsung.

        :param data: Проверяемый аккаунт.
        :return: Словарь с результатами проверки (найден или нет).
        """
        params, headers, cookies = await self._search_prepare()
        response = await self._post_data(params, headers, cookies, data)
        return await self._response_parser(response, data=data)

    @informer(3, "Search preparing")
    async def _search_prepare(self) -> tuple[dict, dict, dict]:
        """Подготавливает данные для поиска.

        :return: Подготовленные данные для поиска: URL параметры, HTML заголовки, куки.
        """
        params = get(self.session, "session.params", "")
        cookies = get(self.session, "session.cookies", "")
        headers = get(self.session, "session.headers", "")
        if not all((cookies, headers)):
            logging.warning('One of the "cookies" or "headers" is empty')
        return params, headers, cookies

    @informer(4, "Posting request")
    @sync_to_async(thread_sensitive=False)
    def _post_data(
        self, params: dict, headers: dict, cookies: dict, data: str | dict
    ) -> Response:
        """Отправляет запрос на сайт Samsung

        :param params: URL параметры.
        :param headers: HTML заголовки.
        :param cookies: Куки.
        :param data: Проверяемый аккаунт.
        :return: Ответ сайта в формате requests.Response.
        """
        response = self.samsung_source(
            params=params,
            headers=headers,
            cookies=cookies,
            search_data=data,
            proxy=self.proxy,
        ).request()
        logging.info(
            f"Samsung response: {ExtStr(response.text).short_html()}, "
            f"status code: {response.status_code}"
        )
        return response

    @informer(5, "Processing response")
    async def _response_parser(self, response: Response, *args, **kwargs) -> dict:
        """Парсит ответ сайта Samsung.

        :param response: Ответ сайта в формате requests.Response.
        :return: Словарь с результатами поиска.
        """
        return self.search_parser().parse(response, *args, **kwargs)
