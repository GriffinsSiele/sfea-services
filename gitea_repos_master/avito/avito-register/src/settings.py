import os
import pathlib

from dotenv import load_dotenv
from putils_logic.putis import PUtils

_current_file_path = pathlib.Path(__file__).parent.absolute()

base_dir = PUtils.bp(_current_file_path, "..")
env = PUtils.bp(base_dir, ".env")
env_example = PUtils.bp(base_dir, ".env.example")

load_dotenv(env if PUtils.is_file_exists(env) else env_example)


MONGO_PORT = os.getenv("MONGO_PORT")
MONGO_HOST = os.getenv("MONGO_HOST")
MONGO_DB = os.getenv("MONGO_DB")
MONGO_COLLECTION = os.getenv("MONGO_COLLECTION")
