import re
from typing import Iterable


class ResponseAdapter:
    @staticmethod
    def cast(response: dict) -> list[dict]:
        adapted_response: dict[str, str | list] = {}
        if not response and not isinstance(response, dict):
            return []

        if not response.get("result"):
            return [{"result": "Не найден"}]

        adapted_response["result"] = "Найден"
        adapted_response["result_code"] = "FOUND"

        if phones := response.get("phones"):
            phones = ResponseAdapter.phones_clean(phones)
            adapted_response["list__phones"] = ResponseAdapter.remove_duplicates(phones)

        if emails := response.get("emails"):
            adapted_response["list__emails"] = ResponseAdapter.remove_duplicates(emails)

        return [adapted_response]

    @staticmethod
    def remove_duplicates(iterable: Iterable) -> list:
        result = set(iterable)
        result.discard("")
        return list(result)

    @staticmethod
    def phones_clean(phones: list) -> list[str]:
        return [re.sub(r"[\(\)\-\ ]", "", phone) for phone in phones]
