import os
import pathlib
from urllib.parse import urlparse

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

TELEGRAM_TOKEN_BOT = os.getenv("TELEGRAM_TOKEN_BOT")
TELEGRAM_CHAT_ID = os.getenv("TELEGRAM_CHAT_ID")

_mongo_url = str(urlparse(MONGO_URL).netloc)
_domain = _mongo_url.split("@")[1] if "@" in _mongo_url else _mongo_url
MONGO_URL_CLEAN = _domain.split(",")[0] + "..." if "," in _domain else _domain
