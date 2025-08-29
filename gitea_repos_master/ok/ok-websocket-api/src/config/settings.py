import os
import pathlib

from dotenv import load_dotenv
from putils_logic.putils import PUtils

_current_file_path = pathlib.Path(__file__).parent.absolute()

base_dir = PUtils.bp(_current_file_path, "..", "..")
env = PUtils.bp(base_dir, ".env")
env_example = PUtils.bp(base_dir, ".env.example")

load_dotenv(env if PUtils.is_file_exists(env) else env_example)

MODE = os.getenv("MODE", "dev")

PROXY_URL = os.getenv("PROXY_URL")

KEYDB_URL = os.getenv("KEYDB_URL")
KEYDB_QUEUE = os.getenv("KEYDB_QUEUE")

CAPTCHA_SERVICE_URL = os.getenv("CAPTCHA_SERVICE_URL", "")

SENTRY_URL = os.getenv("SENTRY_URL")
