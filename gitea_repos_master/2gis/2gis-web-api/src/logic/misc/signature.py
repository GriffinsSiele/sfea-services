import re
from ctypes import c_int32

from src.config.app import SignatureConfig


class Signature:
    def sign(self, url, params):
        string = (
            self._prepare_url(url) + self._prepare_params(params) + SignatureConfig.R_KEY
        )
        return self._encode(string)

    @staticmethod
    def _prepare_params(d):
        def k_to_str(k):
            v = d[k]
            return str(v) if v is not None else ""

        return "".join(k_to_str(k) for k in sorted(d.keys()))

    @staticmethod
    def _rshift(value, n):
        return value >> n if value >= 0 else (value + 0x100000000) >> n

    @staticmethod
    def _prepare_url(url):
        return re.sub(r"^[^/]*//[^/]+/", "/", url.split("?")[0])

    @staticmethod
    def _encode(s):
        r = SignatureConfig.SIGN_C_PARAM
        for i in s:
            r = c_int32(r * SignatureConfig.SIGN_I_PARAM + ord(i)).value
            r = Signature._rshift(r, 0)
        return str(r)
