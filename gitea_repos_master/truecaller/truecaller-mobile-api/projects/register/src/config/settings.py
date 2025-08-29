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

PROXY_URL = os.getenv("PROXY_URL")

SMS_SERVICE_TOKEN = os.getenv("SMS_SERVICE_TOKEN")

COUNT_SESSIONS_ = os.getenv("COUNT_SESSIONS")
COUNT_SESSIONS = int(str(COUNT_SESSIONS_).strip('"')) if COUNT_SESSIONS_ else 180

WEEKEND_REDUCTION_FACTOR_ = os.getenv("WEEKEND_REDUCTION_FACTOR")
WEEKEND_REDUCTION_FACTOR = (
    float(str(WEEKEND_REDUCTION_FACTOR_).strip('"')) if WEEKEND_REDUCTION_FACTOR_ else 1.0
)
