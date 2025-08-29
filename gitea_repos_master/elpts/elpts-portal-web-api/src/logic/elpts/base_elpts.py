import asyncio
from typing import Any, Type

from isphere_exceptions.proxy import ProxyError
from isphere_exceptions.source import SourceError
from isphere_exceptions.worker import InternalWorkerError
from requests import Response

from src.config import ConfigApp
from src.interfaces import (
    AbstractCaptchaService,
    AbstractMainPageParser,
    AbstractPageParser,
    AbstractProxy,
)
from src.logger.context_logger import logging
from src.logic.captcha.captcha_service import CaptchaService
from src.logic.elpts.exceptions import SessionCaptchaDecodeWarning
from src.logic.proxy import Proxy
from src.request_params import (
    ElPtsCaptcha,
    ElPtsMainPage,
    ElPtsPostCaptcha,
    ElPtsPostData,
)


class BaseElPts:
    main_page_parser: AbstractMainPageParser
    post_response_parser: AbstractPageParser
    captcha_response_parser: AbstractPageParser

    _session_id_name = "JSESSIONID"

    def __init__(self) -> None:
        self.proxy_manager: Type[AbstractProxy] = Proxy
        self.captcha_service: Type[AbstractCaptchaService] = CaptchaService
        self.proxy: dict[Any, Any] | None = None
        self.session_id: str | None = None
        self.main_page: dict | None = None
        self.captcha_task_id: str | None = None

    async def prepare(self) -> None:
        logging.info("ElPts preparing ...")

        self.proxy = await self.proxy_manager().get_proxy()
        if not self.proxy:
            raise ProxyError()

        self.session_id, self.main_page = await self._get_main_page()
        if self.main_page and self.session_id:
            return None

        raise SourceError(message="Main page or session id not loaded")

    def clean(self):
        self.main_page = None
        self.session_id = None
        logging.info("ElPts session cleaned.")

    async def search(self, data: str) -> dict:
        logging.info("Step 1. Getting main page info ...")
        if not self.main_page or not self.session_id:
            await self.prepare()

        if not self.main_page:
            logging.info("Step 1. Failed.")
            raise SourceError(message="Main page not loaded")
        if not self.session_id:
            logging.info("Step 1. Failed.")
            raise SourceError(message="Session id not loaded")
        logging.info("Step 1. Success.")

        logging.info(f'Step 2. Submitting "{data}" ...')
        post_response_data = await self._post_data(data, self.main_page, self.session_id)
        logging.info("Step 2. Success.")

        logging.info("Step 3. Getting captcha image ...")
        captcha = await self._get_captcha(
            self.main_page, post_response_data, self.session_id
        )
        logging.info("Step 3. Success.")

        logging.info(
            f"Step 4. Solving captcha image (it may take {ConfigApp.CAPTCHA_TIMEOUT + 5} seconds) ..."
        )
        solved_captcha = await self._solve_captcha(captcha)
        logging.info("Step 4. Success.")

        logging.info("Step 5. Getting search result, checking captcha solution ...")
        search_result = await self._send_captcha(
            solved_captcha, self.main_page, post_response_data, self.session_id
        )
        logging.info("Step 5. Success.")
        return await self._get_search_result(search_result)

    async def _get_main_page(self) -> tuple[str, dict]:
        try:
            response = await ElPtsMainPage(proxy=self.proxy).request()
            logging.info(
                f"Main page status_code: {response.status_code}, cookies: {response.cookies.items()}"
            )
            session_id = response.cookies.get(self._session_id_name)
            return session_id, self._parse_main_page(response.text)
        except asyncio.TimeoutError:
            raise InternalWorkerError(message="Timeout to get main page")

    def _parse_main_page(self, html: str) -> dict:
        return self.main_page_parser.set_page(html).parse_vin()

    async def _post_data(
        self, data: str, main_page: dict, session_id: str, url_suffix: str = "vin"
    ) -> dict:
        post_data = ElPtsPostData(
            data_to_send=data,
            form_link_index=main_page.get("form_link_index", ""),
            input_id=main_page.get("input_id", ""),
            csrf_token=main_page.get("csrf_token_value", ""),
            session_id=session_id,
            url_suffix=url_suffix,
            proxy=self.proxy,
        )
        try:
            response = await post_data.request()
        except asyncio.TimeoutError:
            raise InternalWorkerError(message="Timeout to post search data")
        if response.status_code != 200:
            logging.warning(f"Failed. Post {data} status_code: {response.status_code}")
            raise InternalWorkerError(message="Failed to post search data")

        return self.post_response_parser.set_page(response.text).parse()

    async def _get_captcha(
        self, main_page: dict, post_response_data: dict, session_id: str
    ) -> Response:
        captcha = ElPtsCaptcha(
            captcha_link=post_response_data.get("captcha_link", ""),
            csrf_token=main_page.get("csrf_token_value", ""),
            session_id=session_id,
            proxy=self.proxy,
        )
        try:
            response = await captcha.request()
        except asyncio.TimeoutError:
            raise InternalWorkerError(message="Timeout to get captcha image")
        return response

    async def _solve_captcha(self, response: Response) -> str:
        solved_captcha = None
        solved_time = None
        captcha_response = await self.captcha_service().post_captcha(
            image=response.content, timeout=ConfigApp.CAPTCHA_TIMEOUT
        )

        if captcha_response:
            self.captcha_task_id = captcha_response.get("task_id")
            solved_captcha = captcha_response.get("text")
            solved_time = captcha_response.get("time")

        if solved_captcha:
            logging.info(
                f'Captcha solutions is "{solved_captcha}" (id={self.captcha_task_id}), {solved_time} seconds'
            )
            return solved_captcha

        raise SessionCaptchaDecodeWarning()

    async def _send_captcha(
        self,
        solved_captcha: str,
        main_page: dict,
        post_response_data: dict,
        session_id: str,
    ) -> Response:
        captcha = ElPtsPostCaptcha(
            solved_captcha=solved_captcha,
            captcha_link_index=post_response_data.get("captcha_link_index", ""),
            captcha_input_id=post_response_data.get("captcha_input_id", ""),
            csrf_token=main_page.get("csrf_token_value", ""),
            session_id=session_id,
            proxy=self.proxy,
        )
        try:
            response = await captcha.request()
        except asyncio.TimeoutError:
            raise InternalWorkerError(message="Timeout to post captcha result")
        return response

    async def _get_search_result(self, response: Response) -> dict:
        search_result = self.captcha_response_parser.set_page(response.text).parse()
        captcha_solution = self._check_captcha_solution(search_result)
        if self.captcha_task_id:
            await self.captcha_service().result_report(
                self.captcha_task_id, captcha_solution
            )
            self.captcha_task_id = None
        if not captcha_solution:
            raise SessionCaptchaDecodeWarning()
        logging.info(f"Search result: {search_result}")
        return search_result

    @staticmethod
    def _check_captcha_solution(search_result: dict) -> bool:
        error = search_result.get("errors")
        if error and (
            error[0] == "Указаны неверные символы с картинки"
            or error[0] == "Необходимо ввести проверочные символы"
        ):
            logging.warning("Captcha not accepted")
            return False
        logging.info("Captcha accepted")
        return True
