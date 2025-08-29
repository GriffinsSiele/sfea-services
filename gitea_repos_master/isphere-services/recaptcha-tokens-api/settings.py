from dotenv import load_dotenv
import os

load_dotenv()

APP_PORT = int(os.getenv('APP_PORT'))
MONGO_HOST = os.getenv('MONGO_HOST')
MONGO_PORT = int(os.getenv('MONGO_PORT'))
MONGO_DB = os.getenv('MONGO_DB')
MONGO_COLLECTION = os.getenv('MONGO_COLLECTION')
