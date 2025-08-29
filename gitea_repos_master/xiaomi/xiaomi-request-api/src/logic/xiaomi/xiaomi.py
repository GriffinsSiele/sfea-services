"""
Модуль содержит код для работы с сайтом Xiaomi используя запросы.

Порядок работы с сайтом.
1. Этап подготовки Xiaomi.prepare:
- получение URL параметра "e" со стартовой страницы (условно главной страницы);
- получение капчи с использованием полученного ранее параметра "e";
- решение капчи с использованием сервиса решения капч
- отправка результатов решения капчи на сайт Xiaomi, проверка ответа сайта используя класс XiaomiResponseParser
    - если капча решена на правильно, то в сервис капч направляется отчет об этом,
        поиск прекращается с ошибкой SessionCaptchaDecodeWarning
    - если капча решена правильно, то в сервис капч направляется отчет об этом, класс готов к проверки пользователя.
2. Этап поиска Xiaomi.search(<данные пользователя>):
- телефон или e-mail пользователя отправляется на сайт Xiaomi, в запросе используются полученный на этапе
    подготовки токен решенной капчи.
- ответ сайта Xiaomi обрабатывает класс XiaomiResponseParser
    и возвращает словарь {"result": "Найден", "result_code": "FOUND"}
    или возбуждает исключение NoDataEvent.
"""

from typing import Dict

import pydash
from isphere_exceptions.proxy import ProxyTemporaryUnavailable
from isphere_exceptions.session import SessionCaptchaDecodeError, SessionOutdated
from isphere_exceptions.source import SourceError, SourceParseError
from isphere_exceptions.worker import InternalWorkerError
from requests import JSONDecodeError, Response  # type: ignore
from worker_classes.utils import short

from src.config import ConfigApp
from src.logger.context_logger import logging
from src.logic.captcha import CaptchaService
from src.logic.converters import Base64Converter, Base64ConverterException
from src.logic.parsers import XiaomiResponseParser
from src.logic.proxy import Proxy
from src.logic.xiaomi.exceptions import SessionCaptchaDecodeWarning
from src.request_params import (
    XiaomiCaptchaGet,
    XiaomiCaptchaPost,
    XiaomiMainGet,
    XiaomiSearchResult,
)
from src.utils import informer, now


class Xiaomi:
    """
    Класс для работы с сайтом Xiaomi.

    Переменные класса
    result_parser - класс для парсинга ответов сайта Xiaomi.
    captcha_service - класс для работы с сервисом решения капч.
    proxy_manager - класс для работы с менеджером прокси.
    """

    response_parser = XiaomiResponseParser
    captcha_service = CaptchaService
    proxy_manager = Proxy

    def __init__(self) -> None:
        """Конструктор класса"""
        self.proxy: Dict | None = None
        self.e_param: str = ""
        self.captcha_token: str = ""
        self.search_token: str = ""
        self.image_base64: str = ""
        self.captcha_task_id: str | None = None
        self.captcha_solution: str = ""
        self.captcha_solution_timestamp: int | None = None

    async def prepare(self) -> None:
        """Подготавливает класс к работе. В процессе подготовки получает прокси,
        решает капчу, проверяет результат решения, отправляет ответ в сервис капч.

        :return: None
        """
        logging.info("Xiaomi preparing ...")
        await self._proxy_preparation()
        await self._get_main_info()
        await self._get_captcha_img()
        await self._solve_captcha()
        await self._seng_captcha_solution()
        self.captcha_solution_timestamp = now()

    @informer(0, "Getting proxy")
    async def _proxy_preparation(self) -> None:
        """Проверяет наличие прокси в свойстве класса "proxy",
        если он не установлен, получает прокси от менеджера прокси
        "proxy_manager" и сохраняет его в свойстве "proxy".
        """
        if self.proxy:
            return None

        proxy_dict = await self.proxy_manager().get_proxy()
        if not proxy_dict:
            raise ProxyTemporaryUnavailable()

        self.proxy = proxy_dict

    def clean(self) -> None:
        """Очищает все свойства класса.

        :return: None
        """
        self.e_param = ""
        self.captcha_token = ""
        self.search_token = ""
        self.image_base64 = ""
        self.captcha_solution = ""
        self.captcha_task_id = None
        self.proxy = None
        logging.info("SearchManager cleaned.")

    async def search(self, data: str) -> dict:
        """Поиск информации о пользователе.
        Так как возможно выполнить подготовку поиска и получить решение капчи заранее,
        метод проверяет время решения капчи на устаревание и если это произошло,
        повторно решает капчу перед выполнением поиска.

        :param data: Телефон или e-mail пользователя.
        :return: Словарь {"result": "Найден", "result_code": "FOUND"}, если не найден то исключение NoDataEvent.
        """
        if self._is_outdated_captcha():
            raise SessionOutdated("Captcha is outdated")

        if not self._is_solved_captcha():
            raise InternalWorkerError("Captcha not solved")

        search_result = await self._get_user_info(data)
        logging.info(f"Raw search result: {search_result}")
        return search_result

    @informer(1, "Getting main info")
    async def _get_main_info(self) -> None:
        """Получает URL параметр "e" со стартовой страницы (условно главной страницы),
        который необходим для получения капчи.

        :return: None
        """
        response = await XiaomiMainGet(proxy=self.proxy).request()
        self.e_param = self.response_parser.get_e_query_param(response)
        if not self.e_param:
            raise SourceError('Parameter "e" in main info is empty')

    @informer(2, "Getting captcha image")
    async def _get_captcha_img(self) -> None:
        """Получает изображение с капчей.

        :return: None
        """
        response = await XiaomiCaptchaGet(e_data=self.e_param, proxy=self.proxy).request()
        json_response = self._get_response_json(response, "Main info JSONDecodeError")

        self.image_base64 = pydash.get(json_response, "data.content", "")
        if not self.image_base64:
            raise SourceError("Captcha image base64 is empty")
        self.image_base64 = self.image_base64.replace("\n", "")

        self.captcha_token = pydash.get(json_response, "data.token", "")
        if not self.captcha_token:
            raise SourceError("Captcha image token is empty")

    @informer(
        3,
        f"Solving captcha image (it can take {ConfigApp.CAPTCHA_TIMEOUT + 5} seconds or less)",
    )
    async def _solve_captcha(self) -> None:
        """Решает капчу с использованием сервиса решения капч "captcha_service".

        :return: None
        """
        try:
            img = Base64Converter.covert_to_bytes(self.image_base64)
            captcha_response = await self.captcha_service().post_captcha(
                image=img, timeout=ConfigApp.CAPTCHA_TIMEOUT
            )
        except Base64ConverterException as e:
            logging.warning(f"base64_converter raise exception: {short(e)}")
            raise SourceParseError()
        except SessionCaptchaDecodeError as e:
            logging.warning(f"Captcha service exception: {short(e)}")
            raise SessionCaptchaDecodeWarning()

        if not captcha_response:
            logging.warning("No response received from the captcha service")
            raise SessionCaptchaDecodeWarning()

        captcha_solution = captcha_response.get("text", "")
        captcha_task_id = captcha_response.get("task_id", "")

        if not captcha_solution:
            logging.warning(
                "The captcha service response does not contain the captcha solution"
            )
            raise SessionCaptchaDecodeWarning()

        logging.info(f'Captcha solutions is "{captcha_response}"')
        self.captcha_solution = captcha_solution
        self.captcha_task_id = captcha_task_id

    @informer(4, "Sending captcha solution")
    async def _seng_captcha_solution(self) -> None:
        """Отправляет результат решения капчи на сайт Xiaomi.
        Проверяет правильность решения и отправляет отчет в сервис решения капч "captcha_service".

        :return: None
        """
        response = await XiaomiCaptchaPost(
            captcha_solution=self.captcha_solution,
            captcha_token=self.captcha_token,
            e_data=self.e_param,
            proxy=self.proxy,
        ).request()
        json_response = self._get_response_json(response, "Captcha JSONDecodeError")

        try:
            if self.response_parser.is_captcha_accepted(json_response):
                await self.captcha_service().result_report(self.captcha_task_id, True)
        except SessionCaptchaDecodeWarning as e:
            await self.captcha_service().result_report(self.captcha_task_id, False)
            raise e

        self.search_token = pydash.get(json_response, "data.token")
        if not self.search_token:
            raise SourceError("Search token is empty")

    @informer(
        5,
        "Sending the search key and receiving the search result",
    )
    async def _get_user_info(self, data: str) -> dict:
        """Отправляет данные пользователя на сайт xiaomi и обрабатывает ответ.

        :param data: Телефон или e-mail пользователя.
        :return: Словарь {"result": "Найден", "result_code": "FOUND"}, если не найден то исключение NoDataEvent.
        """
        response = await XiaomiSearchResult(
            search_data=data,
            token=self.search_token,
            proxy=self.proxy,
        ).request()
        return self.response_parser.result_response(response)

    def _is_outdated_captcha(self) -> bool:
        if (
            not self.captcha_solution_timestamp
            or now() - self.captcha_solution_timestamp
            > ConfigApp.CAPTCHA_SOLUTION_TIMESTAMP_LIFETIME
        ):
            logging.info(
                f"Captcha solution is outdated (more than {ConfigApp.CAPTCHA_SOLUTION_TIMESTAMP_LIFETIME} seconds)"
            )
            return True
        return False

    def _is_solved_captcha(self) -> bool:
        if not all((self.captcha_solution, self.captcha_task_id)):
            logging.warning(
                f"One is empty: captcha_solution={self.captcha_solution} or"
                f"captcha_task_id={self.captcha_task_id}"
            )
            return False
        return True

    @staticmethod
    def _get_response_json(response: Response, err_msg: str = "JSONDecodeError") -> dict:
        try:
            return response.json()
        except JSONDecodeError as e:
            logging.warning(f"{err_msg}: {short(e)}")
            raise SourceError()
