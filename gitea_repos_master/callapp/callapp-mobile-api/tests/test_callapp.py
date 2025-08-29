from unittest import IsolatedAsyncioTestCase

from mongo_client.client import MongoSessions
from parameterized import parameterized
from pydash import get, keys, sort
from worker_classes.logger import Logger

from src.config.settings import MONGO_COLLECTION, MONGO_DB, MONGO_URL
from src.logic.callapp.search_manager import SearchCallAppManager
from tests.cases import cases
from tests.utils import UtilsTest


class TestCallApp(IsolatedAsyncioTestCase):
    @classmethod
    def setUpClass(cls):
        Logger().create()

    @parameterized.expand(cases.keys())
    async def test_match_cases(self, phone):
        self.mongo = await MongoSessions(MONGO_URL, MONGO_DB, MONGO_COLLECTION).connect()
        session = await self.mongo.get_session(0, 0)
        case = cases[phone]

        try:
            scam = SearchCallAppManager(auth_data=get(session, "session"))
            await scam.prepare()
            response = await scam.search(phone)
            response = response[0]
            case = cases[phone][0]

            for return_field, expected_field in zip(
                sort(keys(response)), sort(keys(case))
            ):
                self.assertEqual(return_field, expected_field)
                if not UtilsTest.is_ignore_field(return_field):
                    self.assertEqual(response[return_field], case[expected_field])
        except Exception as e:
            self.assertEqual(str(e), case)
