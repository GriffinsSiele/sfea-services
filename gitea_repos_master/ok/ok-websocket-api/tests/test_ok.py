from unittest import IsolatedAsyncioTestCase

from parameterized import parameterized
from pydash import keys, sort
from worker_classes.logger import Logger

from src.logic.ok.search_manager import SearchOKManager
from tests.cases import cases


class TestOK(IsolatedAsyncioTestCase):
    @classmethod
    def setUpClass(cls):
        Logger().create()

    @parameterized.expand(cases.keys())
    async def test_match_cases(self, phone):
        case = cases[phone]

        try:
            sm = SearchOKManager(proxy=True)
            await sm.prepare()
            response = await sm.search(phone)
            for response_user, expected_user in zip(response, case):
                for return_field, expected_field in zip(
                    sort(keys(response_user)), sort(keys(expected_user))
                ):
                    if expected_field in ["avatar", "avatar_cropped"]:
                        continue
                    self.assertEqual(return_field, expected_field)
                    self.assertEqual(
                        response_user[return_field], expected_user[expected_field]
                    )
        except Exception as e:
            self.assertEqual(str(e), case)
