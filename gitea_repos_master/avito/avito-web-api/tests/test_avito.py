from unittest import IsolatedAsyncioTestCase

from isphere_exceptions.source import SourceIncorrectDataDetected
from isphere_exceptions.success import NoDataEvent
from mongo_client.client import MongoSessions
from parameterized import parameterized
from worker_classes.logger import Logger

from src.config.settings import MONGO_COLLECTION, MONGO_DB, MONGO_URL
from src.logic.avito.search_manager import SearchAvitoManager
from tests.cases import cases


class TestAvito(IsolatedAsyncioTestCase):
    @classmethod
    def setUpClass(cls):
        Logger().create()

    @parameterized.expand(cases.keys())
    async def test_match_cases(self, phone):
        case = cases[phone]
        self.mongo = await MongoSessions(MONGO_URL, MONGO_DB, MONGO_COLLECTION).connect()
        session = await self.mongo.get_session()

        try:
            spjm = SearchAvitoManager(auth_data=session["session"])
            await spjm.prepare()
            response = await spjm.search(phone)
            self.assertEqual(response, case)
        except (NoDataEvent, SourceIncorrectDataDetected) as e:
            self.assertEqual(str(e), case)
        except Exception as e:
            self.assertIn("Доступ с вашего", str(e))
