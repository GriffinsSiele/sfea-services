import aiohttp
from requests import Response

from src.captcha.exceptions import CaptchaConfiguredError
from src.config.app import ConfigApp
from src.config.settings import CAPTCHA_PROVIDER
from src.request_params import CaptchaSolverCreate, CaptchaSolverGet, CaptchaSolverReport


class Captcha:
    """Класс для работы с сервисом капч."""

    def __init__(self) -> None:
        """Конструктор класса.
        Устанавливает необходимые для работы сервиса параметры:
        "provider" - наименование провайдера, предоставляющего решение капчи.
        "source" - наименование источника, для которого решается капча.
        """
        self.provider = CAPTCHA_PROVIDER
        self.source = ConfigApp.CAPTCHA_SOURCE
        if not self.provider:
            raise CaptchaConfiguredError("CAPTCHA_PROVIDER is not configured")
        if not self.source:
            raise CaptchaConfiguredError("CAPTCHA_SOURCE is not configured")

    async def send_image(self, captcha_file: bytes, timeout: int = 0) -> Response:
        """Отправляет изображение с капчей в сервис капч. Если время решения не установлено или равно нулю,
        сервиса капч немедленно возвращает ответ, тело которого содержит id задачи и нулевое поле решения капчи.
        Если время на решение капчи установлено и сервис капч решил капчу до истечения времени, то он немедленно
        вернет отвер с решением капчи, если время вышло - вернет ответ как в первом случае с пустым решением.
        Решение карчи можно получить, периодически опрашивая сервис капч методом "get_result".

        :param captcha_file: Изображение с капчей.
        :param timeout: Время на решение капчи.
        :return: Экземпляр класса Response.
        """
        form_data = aiohttp.FormData()
        form_data.add_field(
            "image", captcha_file, filename="captcha.png", content_type="image/png"
        )
        captcha = CaptchaSolverCreate(
            provider=self.provider, source=self.source, timeout=timeout
        )
        response = await captcha.request(data=form_data)
        return response

    @staticmethod
    async def get_result(task_id: str) -> Response:
        """Отправляет запрос в сервис капч на получение результата решения.

        :param task_id: Id задачи на решение капчи.
        :return: Экземпляр класса Response.
        """
        captcha = CaptchaSolverGet(task_id)
        response = await captcha.request()
        return response

    @staticmethod
    async def report(task_id: str, solved_status: bool) -> Response:
        """Отправляет отчет в сервис капч о результате решения капчи.

        :param task_id: Id задачи на решение капчи.
        :param solved_status: Результат решения капчи (True или False).
        :return: Экземпляр класса Response.
        """
        captcha = CaptchaSolverReport(task_id, solved_status)
        response = await captcha.request()
        return response
