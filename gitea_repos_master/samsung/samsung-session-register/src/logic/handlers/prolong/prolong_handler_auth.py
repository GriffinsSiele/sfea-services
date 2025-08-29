from src.parsers import SamsungSearchResultParserAuth

from .prolong_auth import ProlongAuth
from .prolong_handler_common import SessionProlong


class SessionProlongAuth(SessionProlong):
    """Опрашивает сессии с целью продлить их работоспособность"""

    prolong_cls = ProlongAuth
    result_parser_cls = SamsungSearchResultParserAuth
