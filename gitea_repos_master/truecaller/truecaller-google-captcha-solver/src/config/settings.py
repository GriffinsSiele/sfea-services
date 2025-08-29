import os
import pathlib

from dotenv import load_dotenv
from putils_logic.putis import PUtils

_current_file_path = pathlib.Path(__file__).parent.absolute()

base_dir = PUtils.bp(_current_file_path, "..", "..")
env = PUtils.bp(base_dir, ".env")
env_example = PUtils.bp(base_dir, ".env.example")

load_dotenv(env if PUtils.is_file_exists(env) else env_example)

MODE = os.getenv("MODE", "dev")

MONGO_URL = os.getenv("MONGO_URL")
MONGO_DB = os.getenv("MONGO_DB")
MONGO_COLLECTION_RAW = os.getenv("MONGO_COLLECTION")
MONGO_COLLECTION = MONGO_COLLECTION_RAW + "-" + MODE
