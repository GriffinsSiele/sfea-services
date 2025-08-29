from unittest import IsolatedAsyncioTestCase

from mongo_client.client import MongoSessions
from parameterized import parameterized
from worker_classes.logger import Logger

from src.config.settings import MONGO_COLLECTION, MONGO_DB, MONGO_URL
from src.logic.papajohns.search_manager import SearchPapaJohnsManager
from tests.cases import cases


class TestPapaJohns(IsolatedAsyncioTestCase):
    @classmethod
    def setUpClass(cls):
        Logger().create(sensitive_fields=["json:device_token", "json:ja3"])

    @parameterized.expand(cases.keys())
    async def test_match_cases(self, phone):
        self.mongo = await MongoSessions(
            MONGO_URL, db=MONGO_DB, collection=MONGO_COLLECTION
        ).connect()

        case = cases[phone]
        session = await self.mongo.get_session(0, 0)

        try:
            spjm = SearchPapaJohnsManager(auth_data=session["session"])
            await spjm.prepare()
            response = await spjm.search(phone)
            self.assertEqual(response, case)
        except Exception as e:
            self.assertEqual(str(e), case)
