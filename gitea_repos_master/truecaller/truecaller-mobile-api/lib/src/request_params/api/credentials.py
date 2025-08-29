from typing import Optional

from lib.src.logic.device.device import Device
from lib.src.logic.device.generator import DeviceGenerator
from lib.src.request_params.interfaces.authed import AuthedParams


class CredentialsParams(AuthedParams):
    def __init__(self, device: Optional[Device], *args, **kwargs):
        device = device or DeviceGenerator.generate()
        super().__init__(
            url="https://account-noneu.truecaller.com/v2.2/credentials/check",
            *args,
            **{**kwargs, "device": device},
        )
        self.method = "POST"
        self.headers = {
            **self.headers,
            "Host": "account-noneu.truecaller.com",
            "Accept-Encoding": "gzip",
        }
        self.params = {"encoding": "json"}
        self.payload = {
            "device": {
                "deviceId": device.id,
                "manufacturer": device.manufacturer,
                "model": device.model,
            },
            "reason": "received_unauthorized",
        }
