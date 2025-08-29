from typing import Dict

import pydash
from isphere_exceptions.proxy import ProxyTemporaryUnavailable
from isphere_exceptions.session import SessionCaptchaDecodeError
from isphere_exceptions.source import SourceParseError
from isphere_exceptions.success import NoDataEvent
from isphere_exceptions.worker import InternalWorkerError
from requests import JSONDecodeError  # type: ignore
from worker_classes.utils import short

from src.config import ConfigApp
from src.logger.context_logger import logging
from src.logic.apple.exceptions import SessionCaptchaDecodeWarning, SourceWarning
from src.logic.captcha import CaptchaService
from src.logic.converters import Base64Converter, Base64ConverterException
from src.logic.parsers.apple_result_parser import AppleResultParser
from src.logic.proxy import Proxy
from src.request_params import (
    AppleCaptchaGet,
    AppleFormGet,
    AppleFormPost,
    AppleMainGet,
    AppleResultGet,
)
from src.utils import informer, now


class Apple:
    result_parser = AppleResultParser
    captcha_service = CaptchaService
    proxy_manager = Proxy

    def __init__(self) -> None:
        self.proxy: Dict | None = None
        self.captcha_task_id: str | None = None
        self.cookies: Dict = {}
        self.captcha_solution: str = ""
        self.captcha_src: Dict = {}
        self.captcha_solution_timestamp: int | None = None
        self.is_prepared = False

    async def prepare(self) -> None:
        logging.info("Apple preparing ...")
        await self._proxy_preparation()

        await self._get_main_page()  # -> cookies
        await self._get_form()  # -> cookies
        await self._get_captcha()  # -> cookies, captcha_src
        await self._solve_captcha()  # -> captcha_solution, captcha_task_id

        self.is_prepared = True
        self.captcha_solution_timestamp = now()

    @informer(0, "Getting proxy")
    async def _proxy_preparation(self) -> None:
        if self.proxy:
            return None

        proxy_dict = await self.proxy_manager().get_proxy()
        if not proxy_dict:
            raise ProxyTemporaryUnavailable()

        self.proxy = proxy_dict

    def clean(self) -> None:
        self.class_clean()
        self.proxy = None
        logging.info("SearchManager cleaned.")

    def class_clean(self) -> None:
        self.is_prepared = False
        self.cookies = {}
        self.captcha_solution = ""
        self.captcha_task_id = None
        self.captcha_src = {}

    async def search(self, data: str) -> dict:
        if (
            not self.captcha_solution_timestamp
            or now() - self.captcha_solution_timestamp
            > ConfigApp.CAPTCHA_SOLUTION_TIMESTAMP_LIFETIME
        ):
            logging.info(
                f"Captcha solution is outdated (more than {ConfigApp.CAPTCHA_SOLUTION_TIMESTAMP_LIFETIME} seconds)"
            )
            self.class_clean()
            await self.prepare()

        if not self.cookies:
            raise SourceWarning("Empty cookies in source response")

        if not all((self.captcha_solution, self.captcha_task_id, self.captcha_src)):
            logging.warning(
                f"One is empty: captcha_solution={self.captcha_solution};"
                f"captcha_task_id={self.captcha_task_id}; captcha_src={self.captcha_src}"
            )
            raise InternalWorkerError(
                "One is empty: captcha_solution, captcha_task_id or captcha_src"
            )

        search_result = await self._post_form_data(data)
        self.class_clean()
        logging.info(f"Raw search result: {search_result}")
        return search_result

    @informer(1, "Getting main page info")
    async def _get_main_page(self) -> None:
        response = await AppleMainGet(proxy=self.proxy).request()
        self.cookies = response.cookies.get_dict()

    @informer(2, "Getting form page info")
    async def _get_form(self) -> None:
        response = await AppleFormGet(cookies=self.cookies, proxy=self.proxy).request()
        self.cookies = response.cookies.get_dict()

    @informer(3, "Getting captcha image")
    async def _get_captcha(self) -> None:
        response = await AppleCaptchaGet(cookies=self.cookies, proxy=self.proxy).request()
        try:
            json_response = response.json()
        except JSONDecodeError as e:
            raise SourceWarning(f"Captcha JSONDecodeError: {short(e)}")
        if cookies := response.cookies.get_dict():
            self.cookies = cookies
        self.captcha_src = json_response

    @informer(
        4,
        f"Solving captcha image (it can take {ConfigApp.CAPTCHA_TIMEOUT + 5} seconds or less)",
    )
    async def _solve_captcha(self) -> None:
        base64_str = pydash.get(self.captcha_src, "payload.content", "")
        try:
            img = Base64Converter.covert_to_bytes(base64_str)
        except Base64ConverterException as e:
            raise SourceParseError(f"base64_converter raise exception: {short(e)}")

        captcha_response = await self.captcha_service().post_captcha(
            image=img, timeout=ConfigApp.CAPTCHA_TIMEOUT
        )
        if not captcha_response:
            raise SessionCaptchaDecodeError(
                "No response received from the captcha service"
            )

        captcha_solution = captcha_response.get("text", "")
        captcha_task_id = captcha_response.get("task_id", "")
        solved_time = captcha_response.get("time", "")

        if not captcha_solution:
            raise SessionCaptchaDecodeError(
                "The captcha service response does not contain the captcha solution"
            )

        logging.info(
            f'Captcha solutions is "{captcha_solution}" (id={captcha_task_id}), {solved_time} seconds'
        )
        self.captcha_solution = captcha_solution
        self.captcha_task_id = captcha_task_id

    @informer(
        5,
        "Sending the search key and solving the captcha, receiving the search result",
    )
    async def _post_form_data(self, data: str) -> dict:
        try:
            response = await AppleFormPost(
                search_data=data,
                captcha_solution=self.captcha_solution,
                captcha_id=self.captcha_src.get("id", ""),
                captcha_token=self.captcha_src.get("token", ""),
                cookies=self.cookies,
                proxy=self.proxy,
            ).request()
        except Exception as e:
            logging.warning(f"Error sending data to source {short(e)}")
            raise e

        logging.info(
            f"Sending the search key and solving the captcha status code: {response.status_code}"
        )
        if 300 <= response.status_code <= 310:
            location = response.headers.get("Location")
            response = await AppleResultGet(
                cookies=response.cookies.get_dict(), location=location, proxy=self.proxy
            ).request()

        try:
            response = self.result_parser().parse(response)
            await self.captcha_service().result_report(self.captcha_task_id, True)
            return response
        except SessionCaptchaDecodeWarning as e:  # status code 400
            await self.captcha_service().result_report(self.captcha_task_id, False)
            raise e
        except NoDataEvent as e:  # status code 200
            await self.captcha_service().result_report(self.captcha_task_id, True)
            raise e
