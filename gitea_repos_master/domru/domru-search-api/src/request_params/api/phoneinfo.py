from src.config import settings
from src.request_params.interfaces.base import BaseAsyncRequestClient


class PhoneInfoRequestClient(BaseAsyncRequestClient):

    def __init__(self, *args, **kwargs):
        super().__init__(
            url=settings.PHONEINFO_URL,
            params={"source": "rossvyaz"},
            timeout=8,
            *args,
            **kwargs,
        )
