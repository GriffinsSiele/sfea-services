from pymongo import MongoClient
import datetime
from typing import Optional


class IsphereToken:
    def __init__(self, mongoserver: str, mongoport: int, mongodb: str, mongoCollection: str) -> None:
        self.client = MongoClient(mongoserver, mongoport)
        self.db = self.client[mongodb]
        self.tokens = self.db[mongoCollection]

    def get(self, version: str, sitekey: str, action: Optional[str] = None):
        filter_params = {'version': version, 'sitekey': sitekey}
        if action:
            filter_params['action'] = action
        return self.tokens.find_one_and_delete(filter=filter_params, sort=[('createdUTC', 1)])

    def count(self, version: str, sitekey: str, action: str = None):
        filter_params = {'version': version, 'sitekey': sitekey}
        if action:
            filter_params['action'] = action
        return self.tokens.count_documents(filter=filter_params)

    def add(self, TOKEN: str, version: str, sitekey: str, action: str = None) -> None:
        data = {
            'TOKEN': TOKEN,
            'version': version,
            'sitekey': sitekey,
            'action': action,
            'createdUTC': datetime.datetime.utcnow()
        }
        self.tokens.insert_one(data)
