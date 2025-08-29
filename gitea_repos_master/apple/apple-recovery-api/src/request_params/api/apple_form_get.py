from src.config import ConfigApp
from src.request_params.interfaces.apple_base_page import AppleStartPageRequestParams


class AppleFormGet(AppleStartPageRequestParams):
    """Обертка над запросом для получения формы отправки данных с сайта apple.
    Полученная информация будет использована для отправки данных пользователя на сайт apple.
    """

    def __init__(
        self,
        *args: object,
        **kwargs: object,
    ):
        super().__init__(
            url=ConfigApp.FORM_URL,
            *args,
            **kwargs,
        )
        self.headers = {
            **self.headers,
            "Referer": "https://iforgot.apple.com/",
        }
