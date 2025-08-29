from typing import Any, Union

from sqlalchemy import Column

from src.common import enums, exceptions, utils

from .clients import (
    AntigateAPIv2Client,
    CapmonsterAPIv2Client,
    CapmonsterLocalAPIv2Client,
    RucaptchaAPIv2Client,
)


class ProviderAPI(utils.SingletonLogging):
    def __init__(self):
        self.api_clients = {
            enums.ExternalProviderEnum.antigate.value: AntigateAPIv2Client,
            enums.ExternalProviderEnum.capmonster.value: CapmonsterAPIv2Client,
            enums.ExternalProviderEnum.capmonster_local.value: CapmonsterLocalAPIv2Client,
            enums.ExternalProviderEnum.rucaptcha.value: RucaptchaAPIv2Client,
        }

    @property
    def clients_list(self) -> list[str]:
        return sorted(self.api_clients.keys())

    def _initialize_client(self, provider: str | Column[str]):
        if not provider in self.api_clients.keys():
            raise exceptions.BadRequestException(f"Invalid client: {provider}")
        return self.api_clients[provider]()

    def _validate_callback_data(
        self, data: Union[dict[str, Any], bytes], provider: str | Column[str]
    ):
        valid_callback_data_types = {
            enums.ExternalProviderEnum.antigate.value: dict,
            enums.ExternalProviderEnum.capmonster.value: dict,
            enums.ExternalProviderEnum.capmonster_local.value: dict,
            enums.ExternalProviderEnum.rucaptcha.value: bytes,
        }
        callback_data_type = valid_callback_data_types[provider]  # type: ignore[index]
        if not isinstance(data, callback_data_type):
            raise exceptions.BadRequestException(
                message=f"Provider '{provider}' accepts callback data format {callback_data_type}, but received {type(data)}"
            )

    async def submit_image_task(
        self,
        provider: str | Column[str],
        file: bytes,
        callback_url: str,
        solution_specification: dict[str, Any],
    ) -> str:
        client = self._initialize_client(provider=provider)
        return await client.send_image_captcha(
            file=file,
            callback_url=callback_url,
            solution_specification=solution_specification,
        )

    async def request_captcha_solution(
        self, provider: str | Column[str], captcha_id: str | Column[str]
    ) -> str:
        try:
            client = self._initialize_client(provider=provider)
            return await client.request_task_solution(captcha_id=int(captcha_id))
        except ValueError:
            raise exceptions.BadRequestException(
                f"Unable to convert value of 'captcha_id' to int: '{captcha_id}'"
            )

    async def submit_token_task(
        self, provider: str, callback_url: str, website_data: dict[str, Any]
    ) -> str:
        client = self._initialize_client(provider=provider)
        return await client.send_token_captcha(
            callback_url=callback_url, website_data=website_data
        )

    async def send_image_task_report(
        self,
        status: enums.TaskStatusEnum,
        task_data: dict[str, Any],
    ) -> str:
        client = self._initialize_client(provider=task_data["provider"])
        return await client.report_image(captcha_id=task_data["task_id"], status=status)

    async def send_token_task_report(
        self,
        status: enums.TaskStatusEnum,
        task_data: dict[str, Any],
        website_config: dict[str, Any],
    ) -> str:
        client = self._initialize_client(provider=task_data["provider"])
        return await client.report_token(
            captcha_id=task_data["task_id"], status=status, website_config=website_config
        )

    async def request_balance(self, provider: str) -> dict[str, Any]:
        client = self._initialize_client(provider=provider)
        return await client.balance()

    def process_callback(
        self, provider: str | Column[str], data: Union[bytes, dict[str, Any]]
    ) -> dict[str, Any]:
        client = self._initialize_client(provider=provider)
        self._validate_callback_data(data=data, provider=provider)
        return client.process_callback_data(data=data)


service: "ProviderAPI" = ProviderAPI()
