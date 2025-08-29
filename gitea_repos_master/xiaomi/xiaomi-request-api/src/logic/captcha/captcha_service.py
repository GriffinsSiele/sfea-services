from typing import Optional

from isphere_exceptions.session import SessionCaptchaDecodeError
from worker_classes.utils import short

from src.interfaces import AbstractCaptchaService
from src.logger.context_logger import logging

from .captcha import Captcha


class CaptchaService(AbstractCaptchaService):
    """Класс для работы с сервисом решения капчи."""

    captcha = Captcha

    async def post_captcha(self, image: bytes, timeout: int = 0) -> dict | None:
        """Отправляет изображение для решения капчи.

        :param image: Изображение с капчей.
        :param timeout: Максимальное время в течении которого требуется вернуть решение капчи.
        :return: Словарь с результатами решения.
        """
        try:
            response = await self.captcha().send_image(
                captcha_file=image, timeout=timeout
            )
            if response.status_code != 200:
                raise Exception(response.text)
        except Exception as e:
            logging.warning(f"Captcha solved error: {short(e)}")
            raise SessionCaptchaDecodeError()

        return response.json()

    async def result_report(self, task_id: Optional[str], correct: bool) -> bool:
        """Отправляет отчет о решении капчи.

        :param task_id: ID задачи по решению капчи.
        :param correct: Результат решения.
        :return: Результат отправки решения.
        """
        if not task_id:
            return False
        try:
            logging.info(f"Captcha id={task_id} accepted: {correct}")

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
