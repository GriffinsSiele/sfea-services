import re

from isphere_exceptions.session import SessionLocked


class CSRFExtract:

    @staticmethod
    def parse_html(response):
        csrf = re.findall('CSRFToken":"(.*?)"', response.text)
        return csrf[0] if csrf else None

    @staticmethod
    def parse_json(response):
        try:
            d = response.json()
            return d["CSRFToken"]
        except Exception:
            raise SessionLocked()
