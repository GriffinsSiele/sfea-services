from datetime import datetime, timedelta

from pymongo import MongoClient


class MongoSessions:
    def __init__(self, server, port, db, collection):
        self.client = MongoClient(server, int(port))
        self.db = self.client[db]
        self.sessions = self.db[collection]

    def add(self, data: dict):
        payload = {
            "active": True,
            "count_use": 0,
            "count_success": 0,
            "created": datetime.now(),
            "last_use": None,
            "next_use": None,
            **data,
        }
        self.sessions.insert_one(payload)

    def filter_blocked(self):
        return {"$or": [{"next_use": {"$lt": datetime.now()}}, {"next_use": None}]}

    def get_session(self):
        sort = [("last_use", 1)]
        update = {"$set": {"last_use": datetime.now()}, "$inc": {"count_use": 1}}
        filter_ = {"active": True, **self.filter_blocked()}

        return self.sessions.find_one_and_update(filter=filter_, sort=sort, update=update)

    def session_locked(self, session):
        update = {"$set": {"next_use": datetime.now() + timedelta(hours=5)}}
        return self.sessions.update_one(filter={"_id": session["_id"]}, update=update)

    def count_active(self):
        filter_ = {"active": True, **self.filter_blocked()}
        return self.sessions.count_documents(filter=filter_)

    def session_success(self, session):
        self.sessions.update_one(
            filter={"_id": session["_id"]},
            update={"$inc": {"count_success": 1}, "$set": {"next_use": None}},
        )

    def session_inactive(self, session):
        self.sessions.update_one(
            filter={"_id": session["_id"]},
            update={"$set": {"active": False, "next_use": None}},
        )

    def session_update(self, session, payload):
        self.sessions.update_one(filter={"_id": session["_id"]}, update={"$set": payload})
