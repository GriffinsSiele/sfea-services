from unittest import IsolatedAsyncioTestCase

from parameterized import parameterized
from pydash import get, keys, sort
from tests.cases import cases
from tests.utils import UtilsTest
from worker_classes.logger import Logger

from lib.src.logic.mongo.mongo import MongoSessions
from src.config.settings import MONGO_COLLECTION, MONGO_DB, MONGO_URL
from src.logic.ok.search_manager import SearchOKManager


class TestOK(IsolatedAsyncioTestCase):
    @classmethod
    def setUpClass(cls):
        Logger().create()

    @parameterized.expand(cases.keys())
    async def test_match_cases(self, phone):
        await self._test(phone, cases[phone])

    async def _test(self, phone, case):
        self.mongo = await MongoSessions(
            MONGO_URL, db=MONGO_DB, collection=MONGO_COLLECTION
        ).connect()
        session = await self.mongo.get_session()

        sm = SearchOKManager(
            auth_data=session["session"],
            use_proxy=False,
        )
        await sm.prepare()

        response = await sm.search(phone)

        def comparator(v):
            return get(v, "name", "") + get(v, "groups_name", "")

        for return_user, expected_user in zip(
            sort(response, key=comparator), sort(case, key=comparator)
        ):
            for return_field, expected_field in zip(
                sort(keys(return_user)), sort(keys(expected_user))
            ):
                self.assertEqual(return_field, expected_field)
                if not UtilsTest.is_ignore_field(return_field):
                    self.assertEqual(
                        return_user[return_field], expected_user[expected_field]
                    )
