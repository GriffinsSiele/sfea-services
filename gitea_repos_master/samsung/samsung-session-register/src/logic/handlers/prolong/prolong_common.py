from typing import Callable, Type

from isphere_exceptions.proxy import ProxyTemporaryUnavailable
from isphere_exceptions.session import SessionError
from isphere_exceptions.worker import InternalWorkerError
from pydash import get
from requests import Response

from src.interfaces import AbstractSamsungSource
from src.interfaces.abstract_result_parser import AbstractResultParser
from src.logger.context_logger import logging
from src.logic.session_storage import SessionStorage
from src.proxy import Proxy
from src.utils import ExtStr


class Prolong:
    """Отправляет запрос на сайт Samsung с использованием сессии,
    время жизни которой истекает, тем самым продляя существование сессии.
    """

    proxy_service = Proxy
    samsung_source: Type[AbstractSamsungSource]
    random_fake_user_info: Callable

    def __init__(self, session_storage: SessionStorage) -> None:
        """Конструктор класса.

        :param session_storage: Хранилище сессий.
        """
        self.session_storage = session_storage
        self.proxy: dict | None = None

    async def prolong(self, session: dict, response_parser: AbstractResultParser) -> None:
        """Продлевает время жизни сессии, путем отправки запроса на сайт Samsung.

        :param session: Устаревающая сессия, время жизни которой необходимо продлить.
        :param response_parser: Экземпляр класса AbstractResultParser, который обрабатывает ответ сайта Samsung
            и определяет, что сессия отработала корректно и продлена или ее требуется заблокировать
            для дальнейшего восстановления.
        :return: None
        """
        await self._set_proxy(session)
        params, headers, cookies = await self._search_prepare(session)

        search_data = self.random_fake_user_info()
        logging.info(f"Prolong data: {search_data}")

        response = self._post_data(params, headers, cookies, search_data)
        parsed_response = response_parser.parse(response)
        await self._session_processing(parsed_response, session)

    async def _set_proxy(self, session: dict) -> None:
        """Получает прокси на основе данных из сессии (proxy_id), и устанавливает его
        в качестве свойства класса self.proxy. Если требуемый прокси не удалось получить,
        устанавливает случайный прокси.

        :return: None
        """
        proxy_id = get(session, "session.proxy_id")
        if not proxy_id:
            logging.warning("SessionError: Session does not contain proxy id")
            raise SessionError("Session does not contain proxy ID")

        self.proxy = await self.proxy_service().get_proxy_by_id(proxy_id)
        if not self.proxy:
            raise ProxyTemporaryUnavailable()

    @staticmethod
    async def _search_prepare(session: dict) -> tuple[dict, dict, dict]:
        """Подготавливает данные для запроса на сайт Samsung.

        :return: Подготовленные данные для запроса: URL параметры, HTML заголовки, куки.
        """
        params = get(session, "session.params", "")
        cookies = get(session, "session.cookies", "")
        headers = get(session, "session.headers", "")
        if not all((cookies, headers)):
            logging.warning(
                f'One of the "cookies": {ExtStr(cookies).short()} or '
                f'"headers": {ExtStr(headers).short()} is empty'
            )
        return params, headers, cookies

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
            f"Samsung response: {ExtStr(response.text).short()}, "
            f"status code: {response.status_code}"
        )
        return response

    async def _session_processing(self, parsed_response: dict, session: dict) -> None:
        """Обрабатывает сессию в зависимости от результата парсинга ответа сайта Samsung.
        В случае успеха сессия продлена и действий не требуется, иначе блокирует сессию
        для дальнейшего восстановления.

        :param parsed_response: Результат парсинга ответа сайта Samsung.
        :param session: Обрабатываемая сессия.
        :return: None
        """
        key = get(parsed_response, "status")
        if not key:
            raise InternalWorkerError('The "parsed_response" contains incorrect data')

        if key == "success":
            return None

        if key == "blocked":
            await self.session_storage.session_inactive(session)
