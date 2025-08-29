from src.proxy import Proxy
from src.request_params import SamsungSourceName

from ...randomizer import random_fake_person_to_prolong_with_email
from .prolong_common import Prolong


class ProlongName(Prolong):
    """Отправляет запрос на сайт Samsung с использованием сессии person,
    время жизни которой истекает, тем самым продляя существование сессии.
    """

    proxy_service = Proxy
    samsung_source = SamsungSourceName
    random_fake_user_info = random_fake_person_to_prolong_with_email
