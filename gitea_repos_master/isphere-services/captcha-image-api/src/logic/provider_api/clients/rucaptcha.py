from typing import Optional
from urllib.parse import parse_qsl, urlsplit

from src.common import enums, exceptions
from src.config.provider_config import provider_settings

from .antigate import AntigateAPIv2Client


class RucaptchaAPIv2Client(AntigateAPIv2Client):
    def __init__(self):
        super().__init__(
            api_key=provider_settings.RUCAPTCHA_API_KEY,
            service_url="https://api.rucaptcha.com",
            balance_currency="RUB",
        )

    @property
    def report_good_route(self):
        return self.service_url + "/reportCorrect"

    @property
    def report_bad_route(self):
        return self.service_url + "/reportIncorrect"

    async def report_image(
        self, captcha_id: str, status: enums.TaskStatusEnum
    ) -> Optional[str]:
        url = (
            self.report_good_route
            if status is enums.TaskStatusEnum.Success
            else self.report_bad_route
        )
        return await self.send_report(url=url, data={"taskId": int(captcha_id)})

    def process_callback_data(self, data: bytes) -> dict[str, str]:  # type: ignore[override]
        decoded_data = data.decode()
        data_dict = dict(parse_qsl(urlsplit(decoded_data).path))
        solution: str | None = data_dict.get("code")
        if solution is None:
            raise exceptions.BadRequestException(
                "Response does not contain captcha solution"
            )
        if solution.startswith("ERROR"):
            raise exceptions.BadRequestException(
                f"Provider was unable to solve captcha. DETAILS: {data_dict}"
            )
        return {"text": solution}
