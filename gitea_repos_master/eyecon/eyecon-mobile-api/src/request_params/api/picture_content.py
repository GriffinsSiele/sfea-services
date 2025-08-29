from requests_logic.base import RequestBaseParamsAsync


class PictureContentParams(RequestBaseParamsAsync):
    def __init__(self, url, *args, **kwargs):
        headers = {
            "User-Agent": "Dalvik/2.1.0 (Linux; U; Android 11; Mi A2 Build/RQ3A.211001.001)"
        }
        super().__init__(url=url, headers=headers, *args, **kwargs)
