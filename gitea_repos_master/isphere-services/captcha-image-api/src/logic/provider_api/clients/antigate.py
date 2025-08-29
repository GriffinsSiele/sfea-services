from typing import Any, Optional

from src.common import enums, exceptions
from src.config.provider_config import provider_settings

from .baseclient import BaseAPIv2Client


class AntigateAPIv2Client(BaseAPIv2Client):
    def __init__(
        self,
        api_key=provider_settings.ANTICAPTCHA_API_KEY,
        service_url="https://api.anti-captcha.com",
        balance_currency="USD",
    ):
        super().__init__(
            api_key=api_key, service_url=service_url, balance_currency=balance_currency
        )

    @property
    def report_image_bad_route(self):
        return self.service_url + "/reportIncorrectImageCaptcha"

    @property
    def report_recaptcha_good_route(self):
        return self.service_url + "/reportCorrectRecaptcha"

    @property
    def report_recaptcha_bad_route(self):
        return self.service_url + "/reportIncorrectRecaptcha"

    @property
    def report_hcaptcha_bad_route(self):
        return self.service_url + "/reportIncorrectHcaptcha"

    async def report_image(
        self, captcha_id: str, status: enums.TaskStatusEnum
    ) -> Optional[str]:
        url = (
            None
            if status is enums.TaskStatusEnum.Success
            else self.report_image_bad_route
        )
        return await self.send_report(url=url, data={"taskId": int(captcha_id)})

    def process_callback_data(self, data: dict[str, Any]) -> dict[str, Any]:
        if data.get("errorId") != 0:
            err_code, err_desc = data.get("errorCode"), data.get("errorDescription")
            raise exceptions.BadRequestException(
                f"ERR_CODE: {err_code}, ERR_DESC: {err_desc}"
            )
        return self.process_solution(data=data)
