import json
import pathlib

from request_logic.proxy import ProxyManager

from settings import PROXY_PASSWORD, PROXY_LOGIN
from request_logic.path_utils import PUtils

_current_file_path = pathlib.Path(__file__).parent.absolute()


class ProxyConfigManager:
    PROXY_CONFIG = PUtils.bp(_current_file_path, '..', '..', 'proxy_config.json')

    @staticmethod
    def get_proxy():
        if PUtils.is_file_exists(ProxyConfigManager.PROXY_CONFIG):
            return json.load(open(ProxyConfigManager.PROXY_CONFIG, 'r'))

        proxy = ProxyConfigManager.update_proxy()

        return proxy

    @staticmethod
    def update_proxy():
        proxy = ProxyManager({'login': PROXY_LOGIN, 'password': PROXY_PASSWORD}).get_proxy('5')

        with open(ProxyConfigManager.PROXY_CONFIG, 'w') as f:
            f.write(json.dumps(proxy, indent=4))

        return proxy
