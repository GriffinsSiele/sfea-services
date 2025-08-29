from src.config import ConfigApp
from src.request_params.interfaces.elpts_base import ElPtsBaseRequestParams


class ImageParams(ElPtsBaseRequestParams):
    """Используется для получения капчи."""

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.headers = {
            **self.headers,
            "Accept": "image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8",
            "Referer": ConfigApp.BASE_URL + "/portal/index?0",
            "Sec-Fetch-Dest": "image",
            "Sec-Fetch-Mode": "no-cors",
            "Sec-Fetch-Site": "same-origin",
        }
