from dotenv import load_dotenv
import os

load_dotenv()

APP_PORT = int(os.getenv('APP_PORT'))
KEYDB_HOST = os.getenv('KEYDB_HOST')
KEYDB_PASSWORD = os.getenv('KEYDB_PASSWORD')
MONGO_HOST = os.getenv('MONGO_HOST')
