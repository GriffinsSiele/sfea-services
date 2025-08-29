from src.interfaces.abstract_response_adapter import AdaptedResponse, ResponseAdapter


class ResponseNameAdapter(ResponseAdapter):
    @staticmethod
    def cast(response: dict) -> AdaptedResponse:
        adapted_response: dict[str, str | list] = {}
        if not response and not isinstance(response, dict):
            return []

        if not response.get("found"):
            return [{"result": "Не найден"}]

        adapted_response["ResultCode"] = "MATCHED"
        adapted_response["Result"] = "Найден"

        if response.get("phone"):
            adapted_response["Result"] = "Найден, телефон соответствует фамилии и имени"

        if response.get("email"):
            adapted_response["Result"] = "Найден, e-mail соответствует фамилии и имени"

        return [adapted_response]
