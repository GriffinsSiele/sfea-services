from src.request_params.interfaces.base import RequestParams


class CreateChatParams(RequestParams):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.path = "/dk"
        self.redirect = False
        self.query = {"st.cmd": "accountRecoverFeedbackForm"}
