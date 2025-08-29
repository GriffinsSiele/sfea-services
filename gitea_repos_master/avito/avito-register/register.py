import logging

from src.call import SeleniumRegister
from src.mongo import MongoSessions
from src.settings import MONGO_HOST, MONGO_PORT, MONGO_DB, MONGO_COLLECTION

logging.basicConfig(level="INFO")

mongo = MongoSessions(MONGO_HOST, MONGO_PORT, MONGO_DB, MONGO_COLLECTION)

for i in range(50):
    data = SeleniumRegister().register()
    mongo.add({"session": data})
