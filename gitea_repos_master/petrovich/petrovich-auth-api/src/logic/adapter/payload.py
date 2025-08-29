from isphere_exceptions.source import SourceIncorrectDataDetected
from pydash import get


class PayloadType:
    allowed = {"nick": "login", "phone": "phone", "email": "email"}

    @staticmethod
    def parse(payload):
        if not isinstance(payload, dict):
            return payload, "phone"

        for key, value in PayloadType.allowed.items():
            input = get(payload, key)
            if input:
                return input, value

        raise SourceIncorrectDataDetected()
