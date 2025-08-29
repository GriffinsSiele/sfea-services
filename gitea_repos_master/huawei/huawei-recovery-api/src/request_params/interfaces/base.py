from requests_logic.base import RequestBaseParamsAsync


class BaseRequestParams(RequestBaseParamsAsync):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.method = "GET"
        self.timeout = 5
        self.headers = {
            "authority": "contentcenter-dra.dbankcdn.com",
            "accept": "image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8",
            "accept-language": "ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7",
            "referer": "https://id5.cloud.huawei.com/",
            "sec-ch-ua": '"Not=A?Brand";v="99", "Chromium";v="118"',
            "sec-ch-ua-mobile": "?0",
            "sec-ch-ua-platform": '"macOS"',
            "sec-fetch-dest": "image",
            "sec-fetch-mode": "no-cors",
            "sec-fetch-site": "cross-site",
            "user-agent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0.0.0 Iron Safari/537.36",
        }
