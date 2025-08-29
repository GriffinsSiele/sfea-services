from unittest import IsolatedAsyncioTestCase

from mongo_client.client import MongoSessions
from parameterized import parameterized
from pydash import keys, sort
from worker_classes.logger import Logger

from src.config.settings import MONGO_COLLECTION, MONGO_DB, MONGO_URL
from src.logic.simpler.search_manager import SearchSimplerManager
from tests.cases import cases


class TestSimpler(IsolatedAsyncioTestCase):
    @classmethod
    def setUpClass(cls):
        Logger().create()

    @parameterized.expand(cases.keys())
    async def test_match_cases(self, phone):
        case = cases[phone]
        self.mongo = await MongoSessions(MONGO_URL, MONGO_DB, MONGO_COLLECTION).connect()
        session = await self.mongo.get_session()

        try:
            sm = SearchSimplerManager(auth_data=session["session"], proxy=True)
            await sm.prepare()

            response = await sm.search([phone])
            response = response[0]
            for return_field, expected_field in zip(
                sort(keys(response)), sort(keys(case))
            ):
                self.assertEqual(return_field, expected_field)
                self.assertEqual(response[return_field], case[expected_field])
        except Exception as e:
            self.assertEqual(str(e), case)
