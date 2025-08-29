from requests_logic.interfaces.params.cookies import CookiesParams
from requests_logic.interfaces.params.headers import HeadersParams
from requests_logic.interfaces.params.method import MethodParams
from requests_logic.interfaces.params.payload import PayloadParams
from requests_logic.interfaces.params.proxy import ProxyParams
from requests_logic.interfaces.params.query import QueryParams
from requests_logic.interfaces.params.redirect import RedirectParams
from requests_logic.interfaces.params.session import (
    SessionJA3Params,
    SessionParams,
    SessionTLSParams,
)
from requests_logic.interfaces.params.timeout import TimeoutParams
from requests_logic.interfaces.params.url import URLParams
from requests_logic.interfaces.params.verify import VerifyParams


class _RequestParams(
    HeadersParams,
    CookiesParams,
    PayloadParams,
    URLParams,
    MethodParams,
    QueryParams,
    RedirectParams,
    ProxyParams,
    VerifyParams,
    TimeoutParams,
):
    def _request_args(self, **kwargs):
        return {
            "method": self.method,
            "url": self.url,
            self._payload_type: self.payload,
            "headers": self.headers,
            "proxies": self.proxy,
            "params": self.query,
            "cookies": self.cookies,
            "allow_redirects": self.redirect,
            "verify": self.verify,
            "timeout": self.timeout,
            **kwargs,
        }

    def request(self, *args, **kwargs):
        return self.session.request(*args, **self._request_args(), **kwargs)


class RequestJA3Params(_RequestParams, SessionJA3Params):
    def __init__(self, ja3_options=None, *args, **kwargs):
        super().__init__(ja3_options=ja3_options, *args, **kwargs)


class RequestBaseParams(_RequestParams, SessionParams):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)


class RequestTLSParams(_RequestParams, SessionTLSParams):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
