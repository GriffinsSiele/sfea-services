from lib.src.config.app import ConfigApp
from lib.src.request_params.interfaces.base import RequestParams


class ActivateParams(RequestParams):
    def __init__(
        self,
        phone_register: str,
        request_id: str,
        sms_code: str,
        country="in",
        *args,
        **kwargs,
    ):
        super().__init__(
            url="https://account-asia-south1.truecaller.com/v1/verifyOnboardingOtp",
            *args,
            **kwargs,
        )
        self.method = "POST"
        self.headers = {
            **self.headers,
            "Host": "account-asia-south1.truecaller.com",
            "clientSecret": ConfigApp.CLIENT_SECRET,
        }
        self.payload = {
            "countryCode": country,
            "dialingCode": 9,
            "phoneNumber": phone_register,
            "requestId": request_id,
            "token": sms_code,
        }
