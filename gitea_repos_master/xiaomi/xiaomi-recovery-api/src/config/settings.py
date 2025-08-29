import pathlib
from os import getenv

from dotenv import load_dotenv
from putils_logic.putils import PUtils

_current_file_path = pathlib.Path(__file__).parent.absolute()

base_dir = PUtils.bp(_current_file_path, "..", "..")
env = PUtils.bp(base_dir, ".env")
env_example = PUtils.bp(base_dir, ".env.example")

load_dotenv(env if PUtils.is_file_exists(env) else env_example)

MODE = getenv("MODE", "dev")
SENTRY_URL = getenv("SENTRY_URL", "")

PROXY_URL = getenv("PROXY_URL")

RABBITMQ_URL = getenv("RABBITMQ_URL")
RABBITMQ_QUEUE = getenv("RABBITMQ_QUEUE")
_rabbitmq_consumers = getenv("RABBITMQ_CONSUMERS")
RABBITMQ_CONSUMERS = int(_rabbitmq_consumers) if _rabbitmq_consumers else 1

KEYDB_URL = getenv("KEYDB_URL")
KEYDB_QUEUE = getenv("KEYDB_QUEUE")

_implicitly_wait = getenv("IMPLICITLY_WAIT")
IMPLICITLY_WAIT = int(_implicitly_wait) if _implicitly_wait else 1

_explicit_wait_for_link = getenv("EXPLICIT_WAIT_FOR_LINK")
EXPLICIT_WAIT_FOR_LINK = int(_explicit_wait_for_link) if _explicit_wait_for_link else 1

_explicit_wait = getenv("EXPLICIT_WAIT", 1)
EXPLICIT_WAIT = int(_explicit_wait) if _explicit_wait else 1


CAPTCHA_SERVICE_URL = getenv("CAPTCHA_SERVICE_URL", "")
CAPTCHA_PROVIDER = getenv("CAPTCHA_PROVIDER", "")

_chrome_version = getenv("CHROME_VERSION")
CHROME_VERSION = int(_chrome_version) if _chrome_version else 126
