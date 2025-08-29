from src.logic.parsers import SamsungSearchResultParserAuth
from src.logic.samsung.samsung_common import SamsungCommon
from src.request_params import SamsungSourceAuth


class SamsungAuth(SamsungCommon):
    """Осуществляет поиск аккаунта на сайте Samsung."""

    samsung_source = SamsungSourceAuth
    search_parser = SamsungSearchResultParserAuth
