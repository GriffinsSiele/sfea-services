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
SENTRY_URL_HUAWEI = getenv("SENTRY_URL_HUAWEI", "")
SENTRY_URL_HONOR = getenv("SENTRY_URL_HONOR", "")

PROXY_URL = getenv("PROXY_URL")
_PROXY_GROUP = getenv("PROXY_GROUP")
PROXY_GROUP = int(str(_PROXY_GROUP).strip('"')) if _PROXY_GROUP else 1

RABBITMQ_URL = getenv("RABBITMQ_URL")
RABBITMQ_QUEUE_HUAWEI = getenv("RABBITMQ_QUEUE_HUAWEI")
RABBITMQ_QUEUE_HONOR = getenv("RABBITMQ_QUEUE_HONOR")
_rabbitmq_consumers = getenv("RABBITMQ_CONSUMERS")
RABBITMQ_CONSUMERS = int(_rabbitmq_consumers) if _rabbitmq_consumers else 1

KEYDB_URL = getenv("KEYDB_URL")
KEYDB_QUEUE_HUAWEI = getenv("KEYDB_QUEUE_HUAWEI")
KEYDB_QUEUE_HONOR = getenv("KEYDB_QUEUE_HONOR")

_implicitly_wait = getenv("IMPLICITLY_WAIT")
IMPLICITLY_WAIT = int(_implicitly_wait) if _implicitly_wait else 1

_explicit_wait_for_link = getenv("EXPLICIT_WAIT_FOR_LINK")
EXPLICIT_WAIT_FOR_LINK = int(_explicit_wait_for_link) if _explicit_wait_for_link else 1

_explicit_wait = getenv("EXPLICIT_WAIT", 1)
EXPLICIT_WAIT = int(_explicit_wait) if _explicit_wait else 1

_browser_version = getenv("BROWSER_VERSION")
BROWSER_VERSION = int(_browser_version) if _browser_version else 126
