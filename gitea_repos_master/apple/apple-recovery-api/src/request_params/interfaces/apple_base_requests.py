from src.request_params.interfaces.apple_base import AppleBaseParams


class AppleBaseRequestParams(AppleBaseParams):
    """Дополнительные настройки для отправки данных на сайт apple"""

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.headers = {
            **self.headers,
            "Accept": "application/json, text/javascript, */*; q=0.01",
            # "Content-Type": "application/json",
            "Sec-Fetch-Dest": "empty",
            "Sec-Fetch-Mode": "cors",
            "Referer": "https://iforgot.apple.com/",
            "X-Requested-With": "XMLHttpRequest",
        }
