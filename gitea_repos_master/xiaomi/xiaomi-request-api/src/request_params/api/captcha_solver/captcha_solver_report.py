from src.config import settings
from src.request_params.interfaces.captcha_service_base import (
    CaptchaServiceBaseRequestParams,
)


class CaptchaSolverReport(CaptchaServiceBaseRequestParams):
    """Обертка над запросом для отправки результата решения капчи на сервис решения капч"""

    def __init__(
        self,
        task_id: str,
        solved_status: bool,
        *args,
        **kwargs,
    ):
        super().__init__(
            url=settings.CAPTCHA_SERVICE_URL + "/api/tasks/update",
            params={
                "solved_status": "true" if solved_status else "false",
                "task_id": task_id,
            },
            *args,
            **kwargs,
        )
        self.method = "PUT"
