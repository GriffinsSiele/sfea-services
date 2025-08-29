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

MONGO_URL = os.getenv("MONGO_URL")
MONGO_DB = os.getenv("MONGO_DB")
MONGO_COLLECTION_RAW = os.getenv("MONGO_COLLECTION", "")
MONGO_COLLECTION = MONGO_COLLECTION_RAW + "-" + MODE
MONGO_COLLECTION_PRO_RAW = os.getenv("MONGO_COLLECTION_PRO", "")
MONGO_COLLECTION_PRO = MONGO_COLLECTION_PRO_RAW + "-" + MODE

PROXY_URL = os.getenv("PROXY_URL")

KEYDB_URL = os.getenv("KEYDB_URL")
KEYDB_QUEUE = os.getenv("KEYDB_QUEUE")
KEYDB_QUEUE_PRO = os.getenv("KEYDB_QUEUE_PRO")
_KEYDB_TTL_OK = os.getenv("KEYDB_TTL_OK")
KEYDB_TTL_OK = int(str(_KEYDB_TTL_OK).strip('"')) if _KEYDB_TTL_OK else 3600

SENTRY_URL = os.getenv("SENTRY_URL")
