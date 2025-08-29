from datetime import datetime

from pymongo import MongoClient


class MongoSessions:
    def __init__(self, server, port, app, db, collection):
        self.client = MongoClient(server, int(port))
        self.db = self.client[db]
        self.sessions = self.db[collection]
        self.app = app

    def add(self, data: dict):
        payload = {
            'active': True,
            'app': self.app,
            'count_use': 0,
            'count_success': 0,
            'created': datetime.now(),
            'lastuse': None,
            'nextuse': None,
            **data,
        }
        self.sessions.insert_one(payload)

    def get_session(self):
        sort = [('lastuse', 1)]
        update = {'$set': {'lastuse': datetime.now()}, '$inc': {'count_use': 1}}
        filter_ = {
            'app': self.app,
            'active': True,
        }

        return self.sessions.find_one_and_update(filter=filter_, sort=sort, update=update)

    def count_active(self):
        filter_ = {
            'app': self.app,
            'active': True,
        }
        return self.sessions.count_documents(filter=filter_)

    def session_success(self, session):
        self.sessions.update_one(filter={'_id': session['_id']}, update={'$inc': {'count_success': 1}})

    def session_unactive(self, session):
        self.sessions.update_one(filter={'_id': session['_id']}, update={'$set': {'active': False}})
