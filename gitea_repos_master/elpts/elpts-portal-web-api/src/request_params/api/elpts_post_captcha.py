from src.config import ConfigApp
from src.request_params.interfaces.elpts_signed import SignedParams


class ElPtsPostCaptcha(SignedParams):
    def __init__(
        self,
        solved_captcha: str,
        captcha_link_index: str,
        captcha_input_id: str,
        csrf_token: str,
        session_id: str,
        *args,
        **kwargs,
    ):
        self.data = {
            captcha_input_id: "",
            "content:result": solved_captcha,
            "csrftoken": csrf_token,
            "buttonsContainer:buttons:1": "1",
        }
        super().__init__(
            url=ConfigApp.MAIN_PAGE_URL + f"?{captcha_link_index}.IBehaviorListener."
            "1-dialog-content-form-buttonsContainer-buttons-1",
            cookies={
                "csrf-token-name": "csrftoken",
                "csrf-token-value": csrf_token,
                "JSESSIONID": session_id,
            },
            data=self.data,
            *args,
            **kwargs,
        )
        self.solved_captcha = solved_captcha
        self.captcha_link_index = captcha_link_index
        self.captcha_input_id = captcha_input_id
        self.csrf_token = csrf_token
