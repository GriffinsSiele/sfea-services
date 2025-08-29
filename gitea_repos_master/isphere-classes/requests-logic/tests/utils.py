from collections import OrderedDict

from pydash import find, filter_


class UtilsTest:
    @staticmethod
    def prepare_headers(headers):
        unwanted_headers = ['content-length', 'date', 'connection', 'x-amzn-trace-id', 'x-frontend', 'transfer-encoding']
        headers = {k.lower(): v for k, v in headers.items()}

        for unwanted_header in unwanted_headers:
            key = find(list(headers.keys()), lambda k: k.lower() == unwanted_header)
            if key:
                del headers[key]

        return OrderedDict(sorted(headers.items()))

    @staticmethod
    def prepare_json(json):
        unwanted_fields = ['origin']
        output_json = {}
        for k in json.keys():
            if k == 'headers':
                output_json[k] = UtilsTest.prepare_headers(json[k])
            elif k not in unwanted_fields:
                output_json[k] = json[k]
        return output_json

    @staticmethod
    def prepare_cookies(cookies):
        unwanted_cookies = ['remixbdr']
        return filter_(sorted([str(c) for c in cookies]), lambda c: find(unwanted_cookies, lambda uc: c in uc))
