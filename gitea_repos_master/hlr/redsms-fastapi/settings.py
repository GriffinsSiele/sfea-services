from dotenv import load_dotenv
import os

load_dotenv()

KEYDB_HOST = os.getenv('KEYDB_HOST')
KEYDB_PASSWORD = os.getenv('KEYDB_PASSWORD')
APPLICATION = os.getenv('APPLICATION')
