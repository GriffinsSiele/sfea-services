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
PROXY_URL = getenv("PROXY_URL")
SENTRY_URL = getenv("SENTRY_URL", "")
SENTRY_URL_NAME = getenv("SENTRY_URL_NAME", "")

KEYDB_QUEUE = getenv("KEYDB_QUEUE")
KEYDB_QUEUE_NAME = getenv("KEYDB_QUEUE_NAME")
KEYDB_URL = getenv("KEYDB_URL")

DEFAULT_FOLDER = getenv("DEFAULT_FOLDER", "screen_snaps")
IMPLICITLY_WAIT = getenv("IMPLICITLY_WAIT")
EXPLICIT_WAIT_FOR_LINK = getenv("EXPLICIT_WAIT_FOR_LINK")
EXPLICIT_WAIT = getenv("EXPLICIT_WAIT")
MULTIPLE_WAIT = getenv("MULTIPLE_WAIT")
MAX_LINKS_ON_SCREEN = getenv("MAX_LINKS_ON_SCREEN", 3)

RABBITMQ_URL = getenv("RABBITMQ_URL")
RABBITMQ_QUEUE = getenv("RABBITMQ_QUEUE")
RABBITMQ_QUEUE_NAME = getenv("RABBITMQ_QUEUE_NAME")
rabbitmq_consumers = getenv("RABBITMQ_CONSUMERS")
RABBITMQ_CONSUMERS = int(rabbitmq_consumers) if rabbitmq_consumers else 1

TG_BOT_TOKEN = getenv("TG_BOT_TOKEN", "")
TG_CHAT_ID = getenv("TG_CHAT_ID", "")

CAPTCHA_SERVICE_URL = getenv("CAPTCHA_SERVICE_URL", "")
CAPTCHA_PROVIDER = getenv("CAPTCHA_PROVIDER", "")

CHROME_VERSION = int(getenv("CHROME_VERSION", 126))
