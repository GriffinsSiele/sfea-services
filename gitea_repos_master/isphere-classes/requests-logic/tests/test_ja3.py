import os
import unittest
from pathlib import Path

import requests
from dotenv import load_dotenv
from parameterized import parameterized
from requests import Session

from requests_logic.ja3_adapter import JA3Adapter
from tests.cases_match import cases_match
from tests.cases_mismatch import cases_mismatch
from tests.utils import UtilsTest


class TestJA3(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        env = os.path.normpath(os.path.join(Path(__file__).absolute(), '..', '..', '.env'))

        if Path(env).exists():
            load_dotenv(env)

    def create_ja3(self):
        session = Session()
        adapter = JA3Adapter()
        adapter.set_proxy_server_url(os.getenv('JA3_SERVER'))
        adapter.set_ja3_by_index(1)
        session.mount('https://', adapter)
        session.mount('http://', adapter)

        return session

    @parameterized.expand(cases_match.keys())
    def test_match_cases(self, case_key):
        case = cases_match[case_key]
        case_request_params = case['request']

        # JA3
        session = self.create_ja3()
        r1 = session.request(**case_request_params)

        # requests
        r2 = requests.request(**case_request_params)

        compare = case['compare']
        for comparison in compare:
            if comparison == 'status':
                self.assertEqual(r1.status_code, r2.status_code, comparison)
            elif comparison == 'text':
                self.assertEqual(r1.text, r2.text, comparison)
            elif comparison == 'json':
                j1 = UtilsTest.prepare_json(r1.json())
                j2 = UtilsTest.prepare_json(r2.json())
                self.assertEqual(j1, j2, comparison)
            elif comparison == 'headers':
                h1 = UtilsTest.prepare_headers(r1.headers)
                h2 = UtilsTest.prepare_headers(r2.headers)
                self.assertEqual(h1, h2, comparison)
            elif comparison == 'cookies':
                c1 = UtilsTest.prepare_cookies(r1.cookies)
                c2 = UtilsTest.prepare_cookies(r2.cookies)
                self.assertEqual(c1, c2, comparison)

    @parameterized.expand(cases_mismatch.keys())
    def test_mis_match_cases(self, case_key):
        case = cases_mismatch[case_key]
        case_request_params = case['request']

        # JA3
        session = self.create_ja3()
        r1 = session.request(**case_request_params)

        # requests
        r2 = requests.request(**case_request_params)

        match_text = case['match']
        for (text, requester) in match_text.items():
            text_response = r1.text if requester == 'ja3' else r2.text
            self.assertTrue(text in text_response, text)
