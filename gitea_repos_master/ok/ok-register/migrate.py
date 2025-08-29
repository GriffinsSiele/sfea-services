from datetime import datetime

from tqdm import tqdm
from pymongo import MongoClient

from settings import MONGO_HOST, MONGO_PORT, MONGO_DB, MONGO_COLLECTION, MONGO_SERVICE
from src.utils.yaml import YamlFile

client = MongoClient(MONGO_HOST, int(MONGO_PORT))
db = client[MONGO_DB]
collection = db[MONGO_COLLECTION]

ymf = YamlFile('./data/register_b31113.yml')
accounts = ymf.read()

for account in tqdm(accounts):
    data = {
        'PHONE': account['login'],
        'PASSWORD': account['password'],
        'active': True,
        'app': MONGO_SERVICE,
        'session': None,
        'count_success': 0,
        'count_use': 0,
        'created': datetime.now(),
        'lastuse': None,
    }
    collection.insert_one(data)
