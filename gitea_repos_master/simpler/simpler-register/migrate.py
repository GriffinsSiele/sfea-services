from datetime import datetime

from pymongo import MongoClient

from settings import MONGO_HOST, MONGO_PORT, MONGO_DB, MONGO_COLLECTION, MONGO_SERVICE
from src.utils.yaml import YamlFile

client = MongoClient(MONGO_HOST, int(MONGO_PORT))
db = client[MONGO_DB]
collection = db[MONGO_COLLECTION]

ymf = YamlFile('./data/data_2022_07_20.yml')
accounts = ymf.read()

for account in accounts:
    data = {
        'EMAIL': account['email'],
        'TOKEN': account['token'],
        'active': True,
        'app': MONGO_SERVICE,
        'count_success': 0,
        'count_use': 0,
        'created': datetime.now(),
        'lastuse': None,
    }
    collection.insert_one(data)
