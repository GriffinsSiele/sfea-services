import aiohttp
from requests import Response

from src.config import ConfigApp, settings
from src.request_params import CaptchaServiceGet, CaptchaServicePost, CaptchaServicePut


class CaptchaConfiguredError(Exception):
    pass


class Captcha:
    def __init__(self) -> None:
        self.provider = settings.CAPTCHA_PROVIDER
        self.source = ConfigApp.CAPTCHA_SOURCE
        if not self.provider:
            raise CaptchaConfiguredError("CAPTCHA_PROVIDER is not configured")
        if not self.source:
            raise CaptchaConfiguredError("CAPTCHA_SOURCE is not configured")

    async def send_image(self, captcha_file: bytes, timeout: int = 0) -> Response:
        form_data = aiohttp.FormData()
        form_data.add_field(
            "image", captcha_file, filename="captcha.png", content_type="image/png"
        )
        captcha = CaptchaServicePost(
            provider=self.provider, source=self.source, timeout=timeout
        )
        response = await captcha.request(data=form_data)
        return response

    @staticmethod
    async def get_result(task_id: str) -> Response:
        captcha = CaptchaServiceGet(task_id)
        response = await captcha.request()
        return response

    @staticmethod
    async def report(task_id: str, solved_status: bool) -> Response:
        captcha = CaptchaServicePut(task_id, solved_status)
        response = await captcha.request()
        return response
