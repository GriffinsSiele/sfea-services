from src.config.settings import CAPTCHA_SERVICE_URL
from src.request_params.interfaces.captcha_service_base import (
    CaptchaServiceBaseRequestParams,
)


class CaptchaSolverGet(CaptchaServiceBaseRequestParams):
    def __init__(
        self,
        task_id: str,
        *args,
        **kwargs,
    ):
        super().__init__(
            url=CAPTCHA_SERVICE_URL + "/api/tasks/result",
            params={"task_id": task_id},
            *args,
            **kwargs,
        )
