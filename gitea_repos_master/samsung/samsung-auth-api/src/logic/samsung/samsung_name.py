from src.logic.parsers.result_parser_name import SamsungSearchResultParserName
from src.logic.samsung.samsung_common import SamsungCommon
from src.request_params import SamsungSourceName


class SamsungName(SamsungCommon):
    """Осуществляет поиск учетной записи по ФИО и дате рождения на сайте Samsung."""

    samsung_source = SamsungSourceName
    search_parser = SamsungSearchResultParserName
