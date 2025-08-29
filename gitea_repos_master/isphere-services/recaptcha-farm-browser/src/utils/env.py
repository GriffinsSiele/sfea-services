import os
import pathlib

from dotenv import load_dotenv
from request_logic.path_utils import PUtils

_current_file_path = pathlib.Path(__file__).parent.absolute()


def parse_dotenv():
    base_dir = PUtils.bp(_current_file_path, '..', '..')
    env = PUtils.bp(base_dir, '.env')
    env_example = PUtils.bp(base_dir, '.env.example')

    load_dotenv(env if PUtils.is_file_exists(env) else env_example)

    _PROXY_LOGIN = os.getenv('PROXY_LOGIN')
    _PROXY_PASSWORD = os.getenv('PROXY_PASSWORD')

    _MONGO_TOKEN_DB = os.getenv('MONGO_TOKEN_DB')
    _MONGO_TOKEN_PORT = os.getenv('MONGO_TOKEN_PORT')

    _SITE_NAME = os.getenv('SITE_NAME')

    _COUNT_ACTIONS_UNITL_EXIT = int(os.getenv('COUNT_ACTIONS_UNITL_EXIT', 3000))

    return _PROXY_LOGIN, _PROXY_PASSWORD, _MONGO_TOKEN_DB, _MONGO_TOKEN_PORT, _SITE_NAME, _COUNT_ACTIONS_UNITL_EXIT
