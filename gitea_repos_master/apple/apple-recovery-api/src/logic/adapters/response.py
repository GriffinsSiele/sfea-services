from src.fastapi.schemas import AppleDataRecords, AppleSearchResponse


class ResponseAdapter:
    """
    Адаптер ответа. Преобразует данные, полученным от класса Apple в экземпляр класса AppleSearchResponse,
    которой использует FastAPI для отправки ответа на запрос.
    """

    @staticmethod
    def __base_response(
        status: str = "ok",
        code: int = 200,
        message: str = "ok",
        records: AppleDataRecords | None = None,
    ) -> AppleSearchResponse:
        """Формирует ответ в формате AppleSearchResponse.

        :param status: Статус ответа.
        :param code: Код ответа.
        :param message: Сообщение ответа.
        :param records: Результат поиска в формате AppleDataRecords.
        :return: Ответ в формате AppleSearchResponse.
        """
        return AppleSearchResponse(
            status=status,
            code=code,
            message=message,
            records=[records] if records else [],
        )

    @staticmethod
    def ok(response: dict) -> AppleSearchResponse:
        """Формирует положительный ответ, пользователь найден.

        :param response: Данные о пользователе, для включения в ответ.
        :return: Ответ в формате AppleSearchResponse.
        """
        data_records = AppleDataRecords(**response)
        return ResponseAdapter.__base_response(records=data_records)

    @staticmethod
    def error(code: int, message: str) -> AppleSearchResponse:
        """Формирует ответ при ошибке.

        :param code: Код ошибки.
        :param message: Сообщение ошибки.
        :return: Ответ в формате AppleSearchResponse.
        """
        return ResponseAdapter.__base_response(status="error", code=code, message=message)

    @staticmethod
    def get_not_found_response() -> AppleSearchResponse:
        """Формирует ответ пользователь не найден.

        :return: Ответ в формате AppleSearchResponse.
        """
        return AppleSearchResponse(
            status="ok",
            code=204,
            message="Не найден",
        )
