from typing import Optional

from worker_classes.utils import short

from src.captcha.captcha import Captcha
from src.exceptions.exceptions import SessionCaptchaDecodeWarning
from src.interfaces.abstract_captcha_service import AbstractCaptchaService
from src.logger import logging


class CaptchaService(AbstractCaptchaService):
    """Обертка над классом для решения капч, обрабатывает и логирует исключения."""

    captcha = Captcha

    def __init__(self, logger=logging) -> None:
        """Конструктор класса"""
        self.logging = logger

    async def post_captcha(self, image: bytes, timeout: int = 0) -> dict | None:
        """Отправляет изображение с капчей в сервис капч. Если время решения не установлено или равно нулю,
        сервиса капч немедленно возвращает ответ с id задачи и нулевым полем решения капчи.
        Если время на решение капчи установлено и сервис капч решил капчу до истечения времени, то он немедленно
        вернет отвер с решением капчи, если время вышло - вернет ответ как в первом случае с пустым решением.
        Решение карчи можно получить, периодически опрашивая сервис капч методом "get_result".

        :param captcha_file: Изображение с капчей.
        :param timeout: Время на решение капчи.
        :return: Словарь формата {"task_id": 123, "time": 0.12345, "text": "Ww1l8R",
            "accuracy": 0.98765, "provider": "capmonster"}.
        """
        try:
            response = await self.captcha().send_image(
                captcha_file=image, timeout=timeout
            )
            if response.status_code != 200:
                raise Exception(response.text)
        except Exception as e:
            raise SessionCaptchaDecodeWarning(f"Captcha solved error {short(e)}")

        return response.json()

    async def result_report(self, task_id: Optional[str], correct: bool) -> bool:
        """Отправляет отчет в сервис капч о результате решения капчи.
        Данные отчет необходим для сервиса капч (ведение статистики) и не влияет на работу
        использующего данный класс приложения.

        :param task_id: Id задачи на решение капчи.
        :param correct: Результат решения капчи (True или False).
        :return: Результат применения сервисом капч отчета (True или False).
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
