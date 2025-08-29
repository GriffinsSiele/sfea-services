from isphere_exceptions.success import NoDataEvent
from isphere_exceptions.worker import UnknownError
from pydash import get


class ResponseAdapter:

    @staticmethod
    def cast(response):
        is_used = get(response, "data.isUsed")

        if is_used:
            return [{"Result": "Найден", "ResultCode": "FOUND"}]

        if is_used is False:
            raise NoDataEvent()

        raise UnknownError(f"Unknown response: {response}")
