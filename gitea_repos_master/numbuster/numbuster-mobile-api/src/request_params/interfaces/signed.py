from datetime import datetime
from urllib import parse

from src.logic.cipher.signature import SignatureManager
from src.request_params.interfaces.host_resolved import HostResolvedParams


class SignedParams(HostResolvedParams):
    def __init__(self, timestamp=None, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.timestamp = (
            timestamp if timestamp else str(int(round(datetime.now().timestamp())))
        )
        self.query = {
            **self.query,
            "timestamp": self.timestamp,
        }

    @property
    def __request_to_str(self):
        url = self.url.replace("https://", "").replace("http://", "")
        data = {**self.query, **(self.payload if self.payload else {})}
        ordered = {key: data[key] for key in sorted(data.keys())}
        return self.method + url + parse.urlencode(ordered)

    def __create_signature(self):
        signature = SignatureManager.sign(self.__request_to_str)
        return {"signature": signature}

    def __sign(self):
        self.query = {
            **self.query,
            **self.__create_signature(),
        }

    async def request(self, *args, **kwargs):
        self.__sign()
        return await super().request(*args, **kwargs)
