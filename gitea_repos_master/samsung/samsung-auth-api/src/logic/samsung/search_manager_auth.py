from src.logic.samsung.samsung_auth import SamsungAuth
from src.logic.samsung.search_manager_common import SamsungSearchManagerCommon


class SamsungSearchManagerAuth(SamsungSearchManagerCommon):
    """Осуществляет поиск учетной записи пользователя по e-mail"""

    samsung = SamsungAuth
