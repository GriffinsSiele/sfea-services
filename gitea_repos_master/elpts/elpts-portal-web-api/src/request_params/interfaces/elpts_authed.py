from src.config import ConfigApp
from src.request_params.interfaces.elpts_base import ElPtsBaseRequestParams


class AuthedParams(ElPtsBaseRequestParams):
    """Используется для отправки данных на сайт (поисковый запрос и решение капчи)"""

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.headers = {
            **self.headers,
            "Accept": "application/xml, text/xml, */*; q=0.01",
            "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
            "Origin": ConfigApp.BASE_URL,
            "Referer": ConfigApp.BASE_URL + "/portal/index?0",
            "Sec-Fetch-Dest": "empty",
            "Sec-Fetch-Mode": "cors",
            "Sec-Fetch-Site": "same-origin",
            "Wicket-Ajax": "true",
            "Wicket-Ajax-BaseURL": "index?0",
            "Wicket-FocusedElementId": "id8",
            "X-Requested-With": "XMLHttpRequest",
        }
        self.method = "POST"
