from src.parsers.result_parser_person import SamsungSearchResultParserPerson

from .prolong_handler_common import SessionProlong
from .prolong_person import ProlongPerson


class SessionProlongPerson(SessionProlong):
    """Опрашивает сессии с целью продлить их работоспособность"""

    prolong_cls = ProlongPerson
    result_parser_cls = SamsungSearchResultParserPerson
