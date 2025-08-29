import logging
import time

from requests import Response

from src.captcha.captcha_api import CaptchaApi
from src.exceptions import GoogleSessionCaptchaDecodeError
from src.interfaces.abstract_captcha_service import AbstractCaptchaService


class CaptchaService(AbstractCaptchaService):
    captcha = CaptchaApi()

    def solve_captcha(self, image: bytes, timeout: int) -> dict | None:
        start_solve_time = time.time()
        try:
            response = self.captcha.send_image_and_get_result(
                captcha_file=image, timeout=timeout
            )
            if response.status_code != 200:
                raise Exception(response.text)
        except Exception as e:
            logging.warning(f"Captcha solved error: {e}")
            raise GoogleSessionCaptchaDecodeError()

        result = response.json()
        if result.get("text"):
            logging.info(f"Captcha solution result: {result}")
            logging.info(
                f"Captcha received in {round(time.time() - start_solve_time, 2)} seconds."
            )
        return result

    def solve_report(self, task_id: str, correct: bool) -> dict | str | None:
        try:
            response = self.captcha.report(task_id, correct)
            if response.status_code != 200:
                raise Exception(response.text)
        except Exception as e:
            logging.warning(f"Captcha report for {task_id} was not sent correctly: {e}")
            return None

        logging.info(
            "The result of the captcha solution has been sent. "
            f"Status code: {response.status_code}. "
            f"Details: {self._get_response_message(response)}"
        )
        return self._get_response_message(response)

    @staticmethod
    def _get_response_message(response: Response) -> dict | str | None:
        if not isinstance(response, Response):
            return None

        content_type = response.headers.get("Content-Type")
        if "text/plain" == content_type:
            return response.text
        if "application/json" == content_type:
            return response.json()

        return None
