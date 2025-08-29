from unittest import IsolatedAsyncioTestCase

from mongo_client.client import MongoSessions
from parameterized import parameterized
from pydash import keys, sort
from tests.cases import cases
from tests.utils import UtilsTest
from worker_classes.logger import Logger

from src.config.settings import MONGO_COLLECTION, MONGO_DB, MONGO_URL
from src.logic.truecaller.search_manager import SearchTruecallerManager


class TestTruecaller(IsolatedAsyncioTestCase):
    @classmethod
    def setUpClass(cls):
        Logger().create()

    @parameterized.expand(cases.keys())
    async def test_match_cases(self, phone):
        case = cases[phone]

        self.mongo = await MongoSessions(
            MONGO_URL, db=MONGO_DB, collection=MONGO_COLLECTION
        ).connect()
        session = await self.mongo.get_session(count_use=0, next_use_delay=0)

        try:
            scam = SearchTruecallerManager(auth_data=session["session"])
            await scam.prepare()
            response = (await scam.search(phone))[0]
            for return_field, expected_field in zip(
                sort(keys(response)), sort(keys(case))
            ):
                self.assertEqual(return_field, expected_field)
                if not UtilsTest.is_ignore_field(return_field):
                    self.assertEqual(response[return_field], case[expected_field])
        except Exception as e:
            self.assertEqual(str(e), case)
