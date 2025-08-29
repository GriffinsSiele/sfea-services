from lib.src.config.app import ConfigApp
from lib.src.logic.device.device import Device
from lib.src.request_params.interfaces.base import RequestParams


class RegisterParams(RequestParams):
    def __init__(
        self,
        phone_number: str,
        device: Device,
        sequence_no: int = 1,
        country="in",
        *args,
        **kwargs,
    ):
        super().__init__(
            url="https://account-asia-south1.truecaller.com/v2/sendOnboardingOtp",
            *args,
            **{**kwargs, "device": device},
        )
        self.payload = self.__create_payload(device, phone_number, sequence_no, country)
        self.headers = {
            **self.headers,
            "Host": "account-asia-south1.truecaller.com",
            "clientSecret": ConfigApp.CLIENT_SECRET,
        }

    def __create_payload(self, device, phone_number, sequence_no, country):
        return {
            "countryCode": country,
            "dialingCode": 9,
            "installationDetails": {
                "app": {**ConfigApp.APP_VERSION_DICT, "store": "GOOGLE_PLAY"},
                "device": {
                    "deviceId": device.id,
                    "language": "en",
                    "manufacturer": device.manufacturer,
                    "mobileServices": ["GMS"],
                    "model": device.model,
                    "osName": device.os,
                    "osVersion": device.version,
                },
                "language": "en",
                "storeVersion": ConfigApp.APP_VERSION_DICT,
            },
            "sims": [],
            "phoneNumber": phone_number,
            "region": "region-2",
            "sequenceNo": sequence_no,
        }
