from src.interfaces.abstract_captcha_service import AbstractCaptchaService
from src.logger.context_logger import logging
from src.logic.captcha.captcha import Captcha
from src.logic.elpts.exceptions import SessionCaptchaDecodeWarning


class CaptchaService(AbstractCaptchaService):
    captcha = Captcha()

    async def post_captcha(self, image: bytes, timeout: int = 0) -> dict | None:
        try:
            response = await self.captcha.send_image(captcha_file=image, timeout=timeout)
            if response.status_code != 200:
                raise Exception(response.text)
        except Exception as e:
            if "Validation error" in str(e):
                raise SessionCaptchaDecodeWarning("Validation error")
            logging.warning(f"Captcha solved error: {e}")
            raise SessionCaptchaDecodeWarning()

        return response.json()

    async def result_report(self, task_id: str, correct: bool) -> bool:
        try:
            response = await self.captcha.report(task_id, correct)
            if response.status_code != 200:
                raise Exception(response.text)
        except Exception as e:
            logging.warning(
                f"Captcha report for id={task_id} was not sent correctly: {e}"
            )
            return False
        logging.info(f"Captcha report for id={task_id} was sent correctly")
        return True
