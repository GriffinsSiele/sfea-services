from datetime import datetime
from typing import Type

from isphere_exceptions.source import SourceIncorrectDataDetected
from worker_classes.keydb.response_builder import KeyDBResponseBuilder

from src.fastapi.schemas import SamsungSearchDataPerson, SamsungSearchResponse
from src.interfaces import AbstractSamsung
from src.logic.adapters import BirthdateAdapter
from src.logic.samsung.samsung_person import SamsungPerson
from src.logic.samsung.search_manager_common import SamsungSearchManagerCommon


class SamsungSearchManagerPerson(SamsungSearchManagerCommon):
    """
    Осуществляет поиск учетной записи пользователя по имени, фамилии и дате рождения.
    """

    samsung: Type[AbstractSamsung] = SamsungPerson

    async def _search(self, data: SamsungSearchDataPerson, *args, **kwargs) -> dict:
        """Переопределяет базовый метод запуск поиска аккаунта,
        с целью выполнить преобразование поля birthdate.

        :param data: Данные для поиска.
        :param args: Необязательные позиционные аргументы.
        :param kwargs: Необязательные ключевые аргументы.
        :return: Результат поиска.
        """
        try:
            data.birthdate = BirthdateAdapter.to_international_format(data.birthdate)
        except SourceIncorrectDataDetected:
            return SamsungSearchResponse(
                **KeyDBResponseBuilder.error(SourceIncorrectDataDetected())
            )
        return await super()._search(data, *args, **kwargs)

    @staticmethod
    def _birthdate_formatter(birthdate: str) -> str:
        """Преобразует день рождения формата 20.01.1990 в 19900120.

        :param birthdate: День рождения в формате 20.01.1990.
        :return: День рождения в формате 19900120 или исключение SourceIncorrectDataDetected.
        """
        try:
            date_obj = datetime.strptime(birthdate, "%d.%m.%Y")
            return date_obj.strftime("%Y%m%d")
        except ValueError:
            raise SourceIncorrectDataDetected()
