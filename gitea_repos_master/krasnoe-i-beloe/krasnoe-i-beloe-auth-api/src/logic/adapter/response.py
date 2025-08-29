from bs4 import BeautifulSoup
from isphere_exceptions.source import SourceIncorrectDataDetected, SourceParseError
from isphere_exceptions.success import NoDataEvent
from isphere_exceptions.worker import UnknownError


class ResponseAdapter:
    exist_match = "Неверный пароль"
    not_exist_match = "Пользователь с данным номером телефона не зарегистрирован"

    @staticmethod
    def cast(response):
        soup = BeautifulSoup(response.text, "html.parser")

        tags_list = soup.find_all("div", {"class": "form_row error"})
        tags = str(tags_list)

        if not tags_list:
            raise SourceParseError()

        if (
            ResponseAdapter.exist_match in tags
            and ResponseAdapter.not_exist_match in tags
        ):
            raise SourceIncorrectDataDetected()

        if ResponseAdapter.exist_match in tags:
            return [{"Result": "Найден", "ResultCode": "FOUND"}]

        if ResponseAdapter.not_exist_match in tags:
            raise NoDataEvent()

        raise UnknownError(f"Unknown response: {response.text[:100]}")
