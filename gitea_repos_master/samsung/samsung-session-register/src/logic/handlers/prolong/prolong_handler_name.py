from src.parsers.result_parser_name import SamsungSearchResultParserName

from .prolong_handler_common import SessionProlong
from .prolong_name import ProlongName


class SessionProlongName(SessionProlong):
    """Опрашивает сессии с целью продлить их работоспособность"""

    prolong_cls = ProlongName
    result_parser_cls = SamsungSearchResultParserName
