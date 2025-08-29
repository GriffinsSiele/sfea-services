from src.config.settings import CAPTCHA_SERVICE_URL
from src.request_params.interfaces.captcha_service_base import (
    CaptchaServiceBaseRequestParams,
)


class CaptchaSolverReport(CaptchaServiceBaseRequestParams):
    def __init__(
        self,
        task_id: str,
        solved_status: bool,
        *args,
        **kwargs,
    ):
        super().__init__(
            url=CAPTCHA_SERVICE_URL + "/api/tasks/update",
            params={
                "solved_status": "true" if solved_status else "false",
                "task_id": task_id,
            },
            *args,
            **kwargs,
        )
        self.method = "PUT"
