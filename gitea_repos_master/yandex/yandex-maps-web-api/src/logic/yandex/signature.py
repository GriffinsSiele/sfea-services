import urllib.parse
from collections import OrderedDict
from ctypes import c_int32

from src.config.app import SignatureConfig


class Signature:
    def sign(self, query):
        return self.__encode(self.__ordered_query(query))

    def __ordered_query(self, data):
        query = OrderedDict()
        for key in sorted(data.keys()):
            query[key] = data[key]
        return query

    def __encode(self, s):
        s = urllib.parse.urlencode(s, True)

        n = SignatureConfig.SIGN_N
        for char in s:
            n = c_int32(SignatureConfig.SIGN_C * n ^ ord(char)).value

        return n if n > 0 else n + SignatureConfig.SIGN_V
