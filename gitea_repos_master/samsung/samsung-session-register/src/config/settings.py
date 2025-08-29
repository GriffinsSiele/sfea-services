"""
Модель содержит настройки приложения которые задаются из переменных окружения среды
"""

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
_PROXY_GROUP = getenv("PROXY_GROUP")
PROXY_GROUP = int(str(_PROXY_GROUP).strip('"')) if _PROXY_GROUP else 1

MONGO_URL = getenv("MONGO_URL")
MONGO_DB = getenv("MONGO_DB")

MONGO_COLLECTION_RAW = getenv("MONGO_COLLECTION_AUTH", "samsung")
MONGO_COLLECTION_AUTH = MONGO_COLLECTION_RAW + "-" + MODE
MONGO_COLLECTION_RAW = getenv("MONGO_COLLECTION_NAME_PERSON", "samsung")
MONGO_COLLECTION_NAME_PERSON = MONGO_COLLECTION_RAW + "-" + MODE

_COUNT_SESSIONS_AUTH = getenv("COUNT_SESSIONS_AUTH")
COUNT_SESSIONS_AUTH = (
    int(str(_COUNT_SESSIONS_AUTH).strip('"')) if _COUNT_SESSIONS_AUTH else 1
)

_COUNT_SESSIONS_NAME_PERSON = getenv("COUNT_SESSIONS_NAME_PERSON")
COUNT_SESSIONS_NAME_PERSON = (
    int(str(_COUNT_SESSIONS_NAME_PERSON).strip('"')) if _COUNT_SESSIONS_NAME_PERSON else 1
)

_CHECK_SESSION_INTERVAL_AUTH = getenv("CHECK_SESSION_INTERVAL_AUTH")
CHECK_SESSION_INTERVAL_AUTH = (
    int(str(_CHECK_SESSION_INTERVAL_AUTH).strip('"'))
    if _CHECK_SESSION_INTERVAL_AUTH
    else 300
)  # seconds

_CHECK_SESSION_INTERVAL_NAME_PERSON = getenv("CHECK_SESSION_INTERVAL_NAME_PERSON")
CHECK_SESSION_INTERVAL_NAME_PERSON = (
    int(str(_CHECK_SESSION_INTERVAL_NAME_PERSON).strip('"'))
    if _CHECK_SESSION_INTERVAL_NAME_PERSON
    else 300
)  # seconds

SENTRY_URL = getenv("SENTRY_URL", "")
