import ctypes
import re


def prepare_params(d):

    def to_str(k):
        v = d[k]
        return str(v) if v is not None else ''

    return ''.join(to_str(k) for k in sorted(d.keys()))


def prepare_url(url):
    return re.sub(r'^[^/]*\/\/[^/]+\/', '/', url.split('?')[0])


I = 33
C = 5381


def rshift(val, n):
    return val >> n if val >= 0 else (val + 0x100000000) >> n


def encode(s):
    r = C
    for i in s:
        r = r * I + ord(i)
        r = rshift(ctypes.c_int32(r).value, 0)
    return r


def sign(url, params, key):
    return encode(prepare_url(url) + prepare_params(params) + key)

