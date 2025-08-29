import logging
import pathlib

from isphere_exceptions.session import SessionLocked
from proxy_manager import ProxyCacheManager
from putils_logic.putils import PUtils
from pydash import get, pick
from requests_logic.base import RequestBaseParamsAsync

from src.config.settings import PROXY_URL
from src.logic.adapter.csrf import CSRFExtract
from src.logic.winelab.validation import ResponseValidation
from src.request_params.interfaces.auth import AuthParams
from src.request_params.interfaces.base import RequestParams

_current_file_path = pathlib.Path(__file__).parent.absolute()
proxy_cache_manager = ProxyCacheManager(
    proxy_url=PROXY_URL,
    cache_file=PUtils.bp(_current_file_path, "..", "..", "..", "proxy_cache.json"),
    request_class=RequestBaseParamsAsync,
    rate_update=50,
)


class Authorizer:
    def __init__(self, auth_data, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.cookies = get(auth_data, "cookies")
        self.login = get(auth_data, "login")
        self.password = get(auth_data, "password")
        self.csrf = get(auth_data, "csrf")
        self.proxy = None

    async def _prepare(self):
        self.proxy = await proxy_cache_manager.get_proxy(
            query={"proxygroup": "1"},
            repeat=3,
            fallback_query={"proxygroup": "1"},
            adapter="simple",
        )
        logging.info(f"Using proxy: {self.proxy}")

    async def _update_session(self):
        self.csrf = {}
        self.cookies = {}

        r = RequestParams(proxy=self.proxy, cookies=self.cookies)
        response = await ResponseValidation.validate_response(r)

        self.cookies = dict(response.cookies)
        self.csrf = CSRFExtract.parse_html(response)
        if not self.csrf:
            raise SessionLocked("CSRF token not found")

        r = AuthParams(
            csrf=self.csrf,
            login=self.login,
            password=self.password,
            cookies=self.cookies,
            proxy=self.proxy,
        )
        response = await ResponseValidation.validate_response(r)

        self.csrf = CSRFExtract.parse_json(response)
        self.cookies = dict(response.cookies)

    def get_session(self):
        return {
            "cookies": pick(
                self.cookies or {}, "winelabstorefrontRememberMe", "acceleratorSecureGUID"
            ),
            "login": self.login,
            "password": self.password,
            "csrf": self.csrf,
        }
