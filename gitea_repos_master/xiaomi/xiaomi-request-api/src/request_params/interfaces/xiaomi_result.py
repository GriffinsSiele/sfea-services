from src.request_params.interfaces.xiaomi_base import XiaomiBaseParams


class XiaomiResultRequestParams(XiaomiBaseParams):
    """Дополнительные настройки для получения результатов поиска сайта xiaomi"""

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.headers = {
            **self.headers,
            "Accept": "application/json, text/plain, */*",
            "Accept-Language": "ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7",
            "Referer": "https://account.xiaomi.com/helpcenter/service/forgetPassword",
            "DNT": "1",
            "Sec-Fetch-Site": "same-origin",
            "sec-ch-ua": '"Google Chrome";v="125", "Chromium";v="125", "Not.A/Brand";v="24"',
            "User-Agent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36",
        }
