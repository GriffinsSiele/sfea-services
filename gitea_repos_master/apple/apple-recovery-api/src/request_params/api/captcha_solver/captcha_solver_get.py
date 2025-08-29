from src.config import settings
from src.request_params.interfaces.captcha_service_base import (
    CaptchaServiceBaseRequestParams,
)


class CaptchaSolverGet(CaptchaServiceBaseRequestParams):
    """Обертка над запросом для получения решения капчи от сервиса решения капч"""

    def __init__(
        self,
        task_id: str,
        *args,
        **kwargs,
    ):
        super().__init__(
            url=settings.CAPTCHA_SERVICE_URL + "/api/tasks/result",
            params={"task_id": task_id},
            *args,
            **kwargs,
        )
