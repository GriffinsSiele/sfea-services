from typing import Optional

from requests_logic.base import RequestBaseParamsCFFIAsync

from lib.src.config.app import ConfigApp
from lib.src.logic.device.device import Device
from lib.src.logic.device.generator import DeviceGenerator


class RequestParams(RequestBaseParamsCFFIAsync):
    DEFAULT_HEADERS = {
        "Content-Type": "application/json; charset=UTF-8",
        "Host": "account-asia-south1.truecaller.com",
        "Connection": "close",
        "accept-Encoding": "gzip",
    }

    def __init__(self, device: Optional[Device] = None, *args, **kwargs):
        super().__init__(impersonate="chrome99_android", *args, **kwargs)
        device = device or DeviceGenerator.generate()
        self.method = "POST"
        self.timeout = 3
        self.verify = False
        self.headers = {
            **self.DEFAULT_HEADERS,
            "user-agent": f"Truecaller/{ConfigApp.APP_VERSION_STR} ({device.os};{device.version})",
        }
