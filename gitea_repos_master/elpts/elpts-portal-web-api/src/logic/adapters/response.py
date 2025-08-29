from src.fastapi.schemas import ElPtsDataRecords, ElPtsSearchResponse


class ResponseAdapter:
    not_found_errors = {
        "Недостаточно сведений для поиска электронного паспорта.",
        "Введите дополнительные параметры поиска.",
    }

    @staticmethod
    def __base_response(
        status: str = "ok",
        code: int = 200,
        message: str = "ok",
        records: ElPtsDataRecords | None = None,
    ) -> ElPtsSearchResponse:
        return ElPtsSearchResponse(
            status=status,
            code=code,
            message=message,
            records=[records] if records else [],
        )

    @staticmethod
    def ok(response: dict) -> ElPtsSearchResponse:
        errors = response.get("errors")
        if errors and set(errors).intersection(ResponseAdapter.not_found_errors):
            return ResponseAdapter.get_not_found_response()

        data_records = ElPtsDataRecords(**response)
        return ResponseAdapter.__base_response(records=data_records)

    @staticmethod
    def error(code: int, message: str) -> ElPtsSearchResponse:
        return ResponseAdapter.__base_response(status="error", code=code, message=message)

    @staticmethod
    def get_not_found_response() -> ElPtsSearchResponse:
        return ElPtsSearchResponse(
            status="ok",
            code=204,
            message="Не найден",
        )
