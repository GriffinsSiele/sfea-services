import os

from dotenv import load_dotenv
from putils_logic.putis import PUtils

from app.utils.errors import EnvironmentVariableNotDefined, EnvironmentVariableWrong


def __get_int_variable(name: str, default: int) -> int:
    try:
        return int(os.getenv(name, default=default))
    except ValueError:
        raise EnvironmentVariableWrong(name)


__current_file_path = os.path.dirname(os.path.abspath(__file__))
__project_root = PUtils.bp(__current_file_path, "..")
__env = PUtils.bp(__project_root, ".env")

load_dotenv(__env)

ALIASES_CONFIG = PUtils.bp(__project_root, "config", "aliases.yml")

MODE = os.getenv("MODE", default="dev")

POSTGRESQL_URL = os.getenv("POSTGRESQL_URL")
if not POSTGRESQL_URL:
    raise EnvironmentVariableNotDefined("POSTGRESQL_URL")

POSTGRESQL_DB = os.getenv("POSTGRESQL_DB")
if not POSTGRESQL_DB:
    raise EnvironmentVariableNotDefined("POSTGRESQL_DB")

POOL_SIZE = __get_int_variable("POOL_SIZE", 3)

LOGGER_NAME = "uvicorn.error"
LOG_LEVEL = os.getenv("LOG_LEVEL", default="INFO")
LOG_FORMAT = (
    "%(asctime)s - %(name)s - [%(levelname)s] - (%(filename)s).%(funcName)s(%(lineno)d) "
    "- %(message)s"
)
