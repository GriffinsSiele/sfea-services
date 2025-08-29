from isphere_exceptions.source import SourceParseError
from isphere_exceptions.success import NoDataEvent


class ResponseAdapter:
    @staticmethod
    def cast(response):
        if response.text == "true":
            raise NoDataEvent()

        if response.text == "false":
            return [{"Result": "Найден", "ResultCode": "FOUND"}]

        raise SourceParseError()
