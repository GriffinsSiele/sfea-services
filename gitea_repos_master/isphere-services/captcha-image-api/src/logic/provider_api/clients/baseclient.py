import asyncio
from base64 import b64encode
from functools import wraps
from typing import Any, Optional

from aiohttp import ClientSession, client_exceptions
from pydantic.alias_generators import to_camel

from src.common import enums
from src.common.exceptions import BadRequestException
from src.common.logger import Logger
from src.common.utils import SingletonLogging, format_data
from src.logic.token_config import token_config_manager


class BaseAPIv2Client(SingletonLogging):
    @property
    def balance_route(self):
        return self.service_url + "/getBalance"

    @property
    def get_result_route(self):
        return self.service_url + "/getTaskResult"

    @property
    def create_task_route(self):
        return self.service_url + "/createTask"

    def __init__(self, api_key: str, service_url: str, balance_currency: str):
        super().__init__()
        self.api_key = api_key
        self.service_url = service_url
        self.balance_currency = balance_currency
        self.token_config = token_config_manager

    def check_if_error(func):
        @wraps(func)
        async def wrapper(*args, **kwargs):
            response = await func(*args, **kwargs)
            if response.get("errorId") != 0:
                err_code = response.get("errorCode")
                err_desc = response.get("errorDescription")
                raise BadRequestException(f"ERR_CODE: {err_code}, ERR_DESC: {err_desc}")
            return response

        return wrapper

    @check_if_error  # type: ignore[arg-type]
    async def post(self, url: str, data: dict[str, Any], **kwargs) -> dict[str, Any]:
        self.logger.info(
            f"Sending POST request to '{url}'; Body: {Logger.format_body(data)}"
        )
        try:
            async with ClientSession() as session:
                async with session.post(
                    url=url, json=data, verify_ssl=False, timeout=10, **kwargs
                ) as response:
                    payload = await response.json(content_type=None)
                    self.logger.info(
                        f"Received response from '{url}', Body: {Logger.format_body(payload)}, Status: {response.status}"
                    )
                    return payload
        except client_exceptions.ClientError as exc:
            raise BadRequestException(
                message=f"Unable to connect to {url}, Detail: {exc.message if hasattr(exc, 'message') else exc.__str__()}"
            )
        except asyncio.TimeoutError:
            raise BadRequestException(
                message=f"Unable to connect to {url}, Detail: Timeout exceeded."
            )

    def _prepare_data(
        self, exclude_null: bool, exclude_fields: list[str], data: dict[str, Any] = {}
    ) -> dict[str, Any]:
        data = {"clientKey": self.api_key, **data}
        prepared_data = format_data(data, exclude_null, exclude_fields)
        return prepared_data

    def process_solution(self, data: dict[str, Any]) -> dict[str, Any]:
        if data.get("status") == "processing":
            raise BadRequestException("Captcha is not solved yet")
        solution: Optional[dict[str, Any]] = data.get("solution")
        if solution is None:
            raise BadRequestException("Response does not contain captcha solution")
        return solution

    async def _custom_post(
        self,
        url: str,
        data: Optional[dict[str, Any]] = None,
        exclude_null: bool = True,
        exclude_fields: list[str] = [],
    ) -> dict[str, Any]:
        data = data or {}
        prepared_data = self._prepare_data(
            data=data, exclude_null=exclude_null, exclude_fields=exclude_fields
        )
        return await self.post(url, data=prepared_data)

    async def send_report(
        self, url: Optional[str], data: dict[str, Any]
    ) -> Optional[str]:
        if not url:
            return None
        response = await self._custom_post(url=url, data=data)
        return response["status"]

    async def balance(self) -> dict[str, Any]:
        response = await self._custom_post(url=self.balance_route)
        return {"balance": response["balance"], "currency": self.balance_currency}

    async def request_task_solution(
        self,
        captcha_id: int,
    ) -> Any | None:
        response = await self._custom_post(
            url=self.get_result_route, data={"taskId": captcha_id}
        )
        return self.process_solution(data=response)

    async def send_image_captcha(
        self, file: bytes, callback_url: str, solution_specification: dict[str, Any]
    ) -> str:
        data = {
            "task": {
                "type": "ImageToTextTask",
                "body": b64encode(file).decode("ascii"),
                **solution_specification,
            },
            "callbackUrl": callback_url,
        }
        response = await self._custom_post(
            url=self.create_task_route, data=data, exclude_fields=["characters"]
        )
        return str(response["taskId"])

    async def send_token_captcha(
        self, callback_url: str, website_data: dict[str, Any]
    ) -> str:
        website_config: dict[str, Any] = {
            to_camel(k): v for k, v in website_data["website_config"].items()
        }
        type = self.token_config.task_type(
            token_type=website_config.pop("tokenType", None)
        )
        data = {
            "task": {
                "type": type,
                "websiteURL": website_data["url"],
                **website_config,
            },
            "callbackUrl": callback_url,
        }
        response = await self._custom_post(
            url=self.create_task_route,
            data=data,
            exclude_fields=["extraTokenFactor", "provider"],
        )
        return str(response["taskId"])

    async def report_token(
        self,
        captcha_id: str,
        status: enums.TaskStatusEnum,
        website_config: dict[str, Any],
    ) -> Optional[str]:
        slug = self.token_config.report_url_slug(
            token_type=website_config["token_type"],
            provider=website_config["provider"],
            status=status,
        )
        url = self.service_url + slug if slug else None
        return await self.send_report(url=url, data={"taskId": int(captcha_id)})
