import unittest
from parameterized import parameterized

from settings import MONGO_SERVER, MONGO_PORT, MONGO_DB, MONGO_COLLECTION
from src.logic.call import SearchAPI
from src.misc.mongo import MongoSessions
from tests.test_data import cars


class TestAPI(unittest.TestCase):
    @parameterized.expand(cars.keys())
    def test_cars(self, car):

        car_expected = cars[car]

        mongo = MongoSessions(MONGO_SERVER, MONGO_PORT, 'nomerogram', MONGO_DB, MONGO_COLLECTION)
        session = mongo.get_session()

        api = SearchAPI(session['TOKEN'], session['session'])
        car_response = api.search(car)

        self.assertEqual(car_expected, car_response)
