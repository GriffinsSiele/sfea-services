import re


class SessionAdapter:

    @staticmethod
    def parse(response):
        response_cookies = dict(response.cookies)
        allowed_cookies = ["SIK", "SIV"]
        allowed_masks = [{"key": r"^[A-Za-z\d]_?[A-Za-z\d\-_]{7,}$", "value": "^A.*"}]

        def match_regexp(key, value):
            return [
                re.findall(mask.get("key"), key) and re.findall(mask.get("value"), value)
                for mask in allowed_masks
            ]

        cookies = {
            key: value
            for key, value in response_cookies.items()
            if key in allowed_cookies or any(match_regexp(key, value))
        }
        return cookies
