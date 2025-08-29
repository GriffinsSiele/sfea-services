_APP_VERSION = [13, 62, 7]


class ConfigApp:
    APP_VERSION_STR = ".".join([str(n) for n in _APP_VERSION])
    APP_VERSION_DICT = {
        "buildVersion": _APP_VERSION[2],
        "majorVersion": _APP_VERSION[0],
        "minorVersion": _APP_VERSION[1],
    }

    _pad = lambda index, size=2: str(_APP_VERSION[index]).zfill(size)

    APP_VERSION_GRPC = f"{_pad(0)}{_pad(1)}{_pad(2, size=3)}"

    CLIENT_SECRET = "lvc22mp3l1sfv6ujg83rd17btt"
