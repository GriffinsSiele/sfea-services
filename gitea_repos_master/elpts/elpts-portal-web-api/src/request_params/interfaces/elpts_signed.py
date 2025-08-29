import urllib.parse

from requests import Response

from src.config import ConfigApp
from src.request_params.interfaces.elpts_authed import AuthedParams
from src.utils.hmac import Hmac


class SignedParams(AuthedParams):
    data: dict
    headers: dict
    hmac_input_data: str
    csrf_token: str
    hmac = Hmac()

    async def request(self, *args, **kwargs) -> Response:
        self.headers = await self.sign()
        return await super().request(*args, **kwargs)

    async def sign(self) -> dict:
        url = self.url.replace(ConfigApp.BASE_URL, "")
        query_string = urllib.parse.urlencode(self.data)
        hmac_input_data = "#" + self.method + "#" + url + "#" + query_string
        return {
            **self.headers,
            "X-Ajax-Token": await self.hmac.async_hash(hmac_input_data),
            "X-csrftoken": self.csrf_token,
        }
