import re
from typing import Iterable

from src.interfaces.abstract_response_adapter import AdaptedResponse, ResponseAdapter


class ResponseAuthAdapter(ResponseAdapter):
    @staticmethod
    def cast(response: dict) -> AdaptedResponse:
        adapted_response: dict[str, str | list] = {}
        if not response and not isinstance(response, dict):
            return []

        adapted_response["result"] = "Найден"
        adapted_response["result_code"] = "FOUND"

        if devices := response.get("devices"):
            adapted_response["list__devices"] = ResponseAuthAdapter.remove_duplicates(
                devices
            )

        if phones := response.get("phones"):
            phones = ResponseAuthAdapter.phones_clean(phones)
            adapted_response["list__phones"] = ResponseAuthAdapter.remove_duplicates(
                phones
            )

        if emails := response.get("emails"):
            adapted_response["list__emails"] = ResponseAuthAdapter.remove_duplicates(
                emails
            )

        if response.get("android"):
            adapted_response["android"] = "Да"

        if response.get("other"):
            adapted_response["other"] = "Да"

        if response.get("phone_notification") or response.get("email_notification"):
            adapted_response["online"] = "Да"

        if response.get("phone_notification"):
            adapted_response["phone_notification"] = "Да"

        if response.get("email_notification"):
            adapted_response["email_notification"] = "Да"

        if response.get("many_failed_attempts"):
            adapted_response["many_failed_attempts"] = "Да"

        if response.get("aborted_by_user"):
            adapted_response["aborted_by_user"] = "Да"

        if response.get("backup_code"):
            adapted_response["backup_code"] = "Да"

        if response.get("external_auth"):
            adapted_response["external_auth"] = "Да"

        if external_url := response.get("external_url"):
            adapted_response["external_url"] = external_url

        return [adapted_response]

    @staticmethod
    def remove_duplicates(iterable: Iterable) -> list:
        result = set(iterable)
        result.discard("")
        return list(result)

    @staticmethod
    def phones_clean(phones: list) -> list[str]:
        return [re.sub(r"[\(\)\-\ ]", "", phone) for phone in phones]
