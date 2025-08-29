import logging
from urllib.parse import parse_qs, urlparse

from pydash import find, get, unset

from src.logic.misc.signature import Signature
from src.request_params.interfaces.base import RequestParams


class CustomRedirect(RequestParams):
    REDIRECT_CODES = [302, 307]
    DISABLE_SIGNATURE_CHECK_MASKS = [".vchecks.io"]

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.redirect = False

    async def request(self):
        response = await super().request()
        return self.validate(response)

    def validate(self, response):
        if response.status_code in CustomRedirect.REDIRECT_CODES:
            logging.info("Redirect detected")

            url_raw = response.headers["Location"]
            query_raw = parse_qs(urlparse(url_raw).query)

            signature_check = find(
                CustomRedirect.DISABLE_SIGNATURE_CHECK_MASKS,
                lambda m: m in response.url,
            )

            if not signature_check:
                for q in query_raw.keys():
                    query_raw[q] = get(query_raw, f"{q}.0")
                unset(query_raw, "r")
                query_raw["r"] = Signature().sign(url_raw, query_raw)

            q = urlparse(url_raw)

            self.method = response.request.method
            self.cookies = dict(response.cookies)
            self.headers = response.headers
            self.query = query_raw
            self.domain = q.scheme + "://" + q.netloc
            self.path = q.path
            self.redirect = False

            return self.request()
        return response
