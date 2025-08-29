import aiohttp
from requests import Response

from src.config import ConfigApp, settings
from src.request_params import CaptchaSolverCreate, CaptchaSolverGet, CaptchaSolverReport

from .captcha_exceptions import CaptchaConfiguredError


class Captcha:
    """
    Класс для работы с сервисом, который решает капчи.
    """

    def __init__(self) -> None:
        """
        Конструктор класса. Проверяет настройки для работы с сервисом.
        При инициализации на основе настроек приложения определяет провайдера (provider) и источник (source).
        Провайдер - обработчик внутри сервиса капч, который непосредственно решает карчу.
        Источник - кодовое имя приложения, которое запросило решение капчи, необходимо для работы сервиса капч.
        """
        self.provider = settings.CAPTCHA_PROVIDER
        self.source = ConfigApp.CAPTCHA_SOURCE
        if not self.provider:
            raise CaptchaConfiguredError("CAPTCHA_PROVIDER is not configured")
        if not self.source:
            raise CaptchaConfiguredError("CAPTCHA_SOURCE is not configured")

    async def send_image(self, captcha_file: bytes, timeout: int = 0) -> Response:
        """Отправляет изображение для решения капчи.

        :param captcha_file: Изображение с капчей.
        :param timeout: Максимальное время в течении которого требуется вернуть решение капчи.
            Если время вышло, а капча еше не решена, данный сервис вернет ответ с пустым решением и id задачи,
            по которому решение можно забрать позже.
        :return: Экземпляр класса `requests.Response`.
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
        """Получить результат решения капчи по id задачи.

        :param task_id: ID задачи.
        :return: Экземпляр класса `requests.Response`.
        """
        captcha = CaptchaSolverGet(task_id)
        response = await captcha.request()
        return response

    @staticmethod
    async def report(task_id: str, solved_status: bool) -> Response:
        """Отправляет отчет о правильности решения капчи.
        Данная информация необходима сервису капч для накопления информации и опциональна для самого обработчика.

        :param task_id: ID задачи по решению капчи.
        :param solved_status: Результат решения.
        :return: Экземпляр класса `requests.Response`.
        """
        captcha = CaptchaSolverReport(task_id, solved_status)
        response = await captcha.request()
        return response
