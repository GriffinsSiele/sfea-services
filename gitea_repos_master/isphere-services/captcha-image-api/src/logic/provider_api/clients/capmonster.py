from typing import Optional

from src.common import enums
from src.config.provider_config import provider_settings

from .antigate import AntigateAPIv2Client


class CapmonsterAPIv2Client(AntigateAPIv2Client):
    def __init__(
        self,
        api_key=provider_settings.CAPMONSTER_API_KEY,
        service_url="https://api.capmonster.cloud",
    ):
        super().__init__(
            api_key=api_key,
            service_url=service_url,
        )

    async def report_image(
        self, captcha_id: str, status: enums.TaskStatusEnum
    ) -> Optional[str]:
        url = (
            None
            if status is enums.TaskStatusEnum.Success
            else self.report_image_bad_route
        )
        return await self.send_report(url=url, data={"taskId": int(captcha_id)})


class CapmonsterLocalAPIv2Client(CapmonsterAPIv2Client):
    def __init__(
        self,
        api_key=provider_settings.CAPMONSTER_API_KEY,
        service_url=provider_settings.CAPMONSTER_LOCAL_URL,
    ):
        super().__init__(
            api_key=api_key,
            service_url=service_url,
        )
