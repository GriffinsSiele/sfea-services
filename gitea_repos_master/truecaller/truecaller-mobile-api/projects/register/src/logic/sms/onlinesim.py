import re

from onlinesimru import NumbersService, UserService
from pydash import get
from pydash.helpers import deprecated


@deprecated
class OnlineSIMAPI:
    def __init__(self, options: dict):
        self.token = options["token"]
        self.numbers = NumbersService(self.token)
        self.client = UserService(self.token)

    def balance(self) -> int:
        return self.client.balance()

    def get_truecaller(self) -> dict:
        response = self.numbers.get("TrueCaller", country=7, number=True)
        return {
            **response,
            "short_number": get(response, "number", "").replace("+7", ""),
        }

    def get_sms(self, tzid) -> str:
        message = self.numbers.wait_code(tzid)
        return re.findall(r"\d+", message)[0]
