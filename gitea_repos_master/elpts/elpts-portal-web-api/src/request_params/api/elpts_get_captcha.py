from src.request_params.interfaces.elpts_img import ImageParams


class ElPtsCaptcha(ImageParams):
    def __init__(
        self, captcha_link: str, csrf_token: str, session_id: str, *args, **kwargs
    ):
        super().__init__(url=captcha_link, *args, **kwargs)
        self.cookies = {
            "csrf-token-name": "csrftoken",
            "csrf-token-value": csrf_token,
            "JSESSIONID": session_id,
        }
