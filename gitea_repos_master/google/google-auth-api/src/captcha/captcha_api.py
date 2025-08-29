import requests

from src.config.app import ConfigApp
from src.config.settings import CAPTCHA_PROVIDER, CAPTCHA_SERVICE_URL


class CaptchaApi:
    MIN_TIMEOUT = 3  # минимальный timeout ожидания ответа от сети
    HEADERS = {"accept": "application/json"}

    def send_image_and_get_result(
        self, captcha_file: bytes, timeout: int
    ) -> requests.Response:
        post_params: dict[str, str | int] = {
            "provider": CAPTCHA_PROVIDER,
            "source": ConfigApp.CAPTCHA_SOURCE,
            "timeout": timeout,
        }
        files = {"image": ("captcha.png", captcha_file, "image/png")}

        return requests.post(
            url=self.send_image_url,
            params=post_params,
            headers=self.HEADERS,
            files=files,
            timeout=self.MIN_TIMEOUT + timeout,
        )

    def report(self, task_id: str, solved_status: bool) -> requests.Response:
        params = {
            "task_id": task_id,
            "solved_status": "true" if solved_status else "false",
        }

        return requests.put(
            url=self.send_report_url,
            params=params,
            headers=self.HEADERS,
            timeout=self.MIN_TIMEOUT,
        )

    @property
    def send_image_url(self):
        return CAPTCHA_SERVICE_URL + "/api/decode/image"

    @property
    def send_report_url(self):
        return CAPTCHA_SERVICE_URL + "/api/tasks/update"
