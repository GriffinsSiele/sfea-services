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

    _KEYDB_HOST = os.getenv('KEYDB_HOST')
    _KEYDB_PASSWORD = os.getenv('KEYDB_PASSWORD')

    _MONGO_SERVER = os.getenv('MONGO_SERVER')
    _MONGO_PORT = os.getenv('MONGO_PORT')
    _MONGO_DB = os.getenv('MONGO_DB')
    _MONGO_COLLECTION = os.getenv('MONGO_COLLECTION')

    return _PROXY_LOGIN, _PROXY_PASSWORD, _KEYDB_HOST, _KEYDB_PASSWORD, _MONGO_SERVER, _MONGO_PORT, _MONGO_DB, _MONGO_COLLECTION