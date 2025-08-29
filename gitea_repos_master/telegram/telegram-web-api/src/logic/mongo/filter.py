from datetime import datetime, timedelta


def update_filter(mongo):
    new_filter = {
        "active": True,
        "session.last_message": {"$gt": datetime.now() - timedelta(days=1)},
    }
    mongo.default_filter = new_filter
