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

    _SELENIUM_BROWSER_DRIVER_PATH = os.getenv('SELENIUM_BROWSER_DRIVER_PATH')
    _SMS_ACTIVATE_TOKEN = os.getenv('SMS_ACTIVATE_TOKEN')

    return _SELENIUM_BROWSER_DRIVER_PATH, _SMS_ACTIVATE_TOKEN
