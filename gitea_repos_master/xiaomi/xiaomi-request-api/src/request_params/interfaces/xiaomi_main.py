from src.config import ConfigApp
from src.request_params.interfaces.xiaomi_base import XiaomiBaseParams


class XiaomiMainRequestParams(XiaomiBaseParams):
    """Дополнительные настройки для получения URL адреса с сайта xiaomi
    который содержит параметры строки, необходимые для получения капчи.
    """

    timestamp: int

    def __init__(self, params=None, *args, **kwargs):
        params = params or {}
        super().__init__(
            params={
                **params,
                "k": ConfigApp.K_PARAM,
                "locale": "en_US",
                "_t": self.timestamp,
            },
            *args,
            **kwargs,
        )
        self.headers = {
            **self.headers,
            "authority": "verify.sec.xiaomi.com",
            "accept": "*/*",
            "accept-language": "en-US,en;q=0.9",
            "priority": "u=1, i",
            "referer": "https://account.xiaomi.com/",
            "sec-fetch-site": "same-site",
            "sec-ch-ua": '"Chromium";v="121", "Not A(Brand";v="99"',
            "user-agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.6167.160 Safari/537.36",
        }
