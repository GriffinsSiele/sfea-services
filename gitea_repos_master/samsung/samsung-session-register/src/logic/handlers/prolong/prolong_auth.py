from src.logic.randomizer import random_fake_email
from src.proxy import Proxy
from src.request_params import SamsungSourceAuth

from .prolong_common import Prolong


class ProlongAuth(Prolong):
    """Отправляет запрос на сайт Samsung с использованием сессии auth,
    время жизни которой истекает, тем самым продляя существование сессии.
    """

    proxy_service = Proxy
    samsung_source = SamsungSourceAuth
    random_fake_user_info = random_fake_email
