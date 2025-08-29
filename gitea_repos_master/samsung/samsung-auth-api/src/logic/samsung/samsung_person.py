from src.logic.parsers import SamsungSearchResultParserPerson
from src.logic.samsung.samsung_common import SamsungCommon
from src.request_params import SamsungSourcePerson


class SamsungPerson(SamsungCommon):
    """Осуществляет поиск учетной записи по ФИО и дате рождения на сайте Samsung."""

    samsung_source = SamsungSourcePerson
    search_parser = SamsungSearchResultParserPerson
