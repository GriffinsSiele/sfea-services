import random
import string

from settings import MONGO_SERVER, MONGO_PORT, MONGO_DB, MONGO_COLLECTION
from src.logic.call import SearchAPI
from src.misc.mongo import MongoSessions

mongo = MongoSessions(MONGO_SERVER, MONGO_PORT, 'nomerogram', MONGO_DB, MONGO_COLLECTION)

for i in range(30):
    device_id = ''.join([random.choice(string.digits + 'abcdef') for _ in range(32)])
    cars = ['Р548се178', 'Х909хх47', 'Х200АМ178', 'Р001ТМ78']
    api = SearchAPI(device_id, {}, with_random_proxy=False)
    response = api.search(random.choice(cars))
    cookies = api.get_cookies()

    payload = {'TOKEN': device_id, 'session': cookies}
    mongo.add(payload)
    print(i, payload)
