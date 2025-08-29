from unittest import IsolatedAsyncioTestCase

from mongo_client.client import MongoSessions
from parameterized import parameterized
from pydash import keys, sort, sort_by
from worker_classes.logger import Logger

from src.config.settings import MONGO_COLLECTION, MONGO_DB, MONGO_URL
from src.logic.yandex.search_manager import SearchYandexMapsManager
from tests.cases import cases
from tests.utils import UtilsTest


class TestYaMap(IsolatedAsyncioTestCase):
    @classmethod
    def setUpClass(cls):
        Logger().create()

    @parameterized.expand(cases.keys())
    async def test_match_cases(self, phone):
        case = cases[phone]
        self.mongo = await MongoSessions(MONGO_URL, MONGO_DB, MONGO_COLLECTION).connect()
        session = await self.mongo.get_session()

        symm = SearchYandexMapsManager(auth_data=session["session"], proxy=False)
        await symm.prepare()
        response = await symm.search(phone)

        for return_org, expected_org in zip(
            sort_by(response, "_id"), sort_by(case, "_id")
        ):
            for return_field, expected_field in zip(
                sort(keys(return_org)), sort(keys(expected_org))
            ):
                self.assertEqual(return_field, expected_field)
                if not UtilsTest.is_ignore_field(return_field):
                    self.assertEqual(
                        return_org[return_field], expected_org[expected_field]
                    )
