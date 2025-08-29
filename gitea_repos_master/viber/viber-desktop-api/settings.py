from dotenv import load_dotenv
import os

load_dotenv()

APP = os.getenv("APP")
MODE = os.getenv("MODE")
MONGO_URL = os.getenv("MONGO_URL")
MONGO_DB = os.getenv("MONGO_DB")
MONGO_COLLECTION = f"{APP}-{MODE}"
KEYDB_URL = os.getenv("KEYDB_URL")
USE_BEFORE_EXIT = int(os.getenv("USE_BEFORE_EXIT", 1000))
POD = os.uname().nodename
MODE = os.getenv("MODE", "dev")
