import os
import pathlib

from dotenv import load_dotenv

from src.utils.pUtils import bp, is_file_exists

_current_file_path = pathlib.Path(__file__).parent.absolute()


def parse_dotenv():
    base_dir = bp(_current_file_path, '..', '..')
    env = bp(base_dir, '.env')
    env_example = bp(base_dir, '.env.example')

    load_dotenv(env if is_file_exists(env) else env_example)

    _MONGO_HOST = os.getenv('MONGO_HOST')
    _MONGO_PORT = os.getenv('MONGO_PORT')
    _MONGO_DB = os.getenv('MONGO_DB')
    _MONGO_COLLECTION = os.getenv('MONGO_COLLECTION')
    _MONGO_SERVICE = os.getenv('MONGO_SERVICE')

    return _MONGO_HOST, _MONGO_PORT, _MONGO_DB, _MONGO_COLLECTION, _MONGO_SERVICE
