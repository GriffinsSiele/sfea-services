from src.config import ConfigApp
from src.request_params.interfaces.apple_base_requests import AppleBaseRequestParams


class AppleFormPost(AppleBaseRequestParams):
    """Обертка над запросом для отправки данных пользователя по которому
    осуществляется поиск и результата решения капчи на сайт apple
    """

    def __init__(
        self,
        search_data: str,
        captcha_solution: str,
        captcha_id: str,
        captcha_token: str,
        *args,
        **kwargs,
    ):
        super().__init__(
            url=ConfigApp.FORM_URL,
            json={
                "id": search_data,
                "captcha": {
                    "id": captcha_id,
                    "answer": captcha_solution,
                    "token": captcha_token,
                },
            },
            allow_redirects=False,
            *args,
            **kwargs,
        )
        self.method = "POST"
        self.headers = {
            **self.headers,
            "Content-Type": "application/json",
            "Origin": ConfigApp.BASE_URL,
            # "X-Apple-I-FD-Client-Info": ""
            # "sstt": ""
        }
