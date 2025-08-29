from src.logic.randomizer import random_fake_person_to_prolong
from src.proxy import Proxy
from src.request_params import SamsungSourcePerson

from .prolong_common import Prolong


class ProlongPerson(Prolong):
    """Отправляет запрос на сайт Samsung с использованием сессии person,
    время жизни которой истекает, тем самым продляя существование сессии.
    """

    proxy_service = Proxy
    samsung_source = SamsungSourcePerson
    random_fake_user_info = random_fake_person_to_prolong
