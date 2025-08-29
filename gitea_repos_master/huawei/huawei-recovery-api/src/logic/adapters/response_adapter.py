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
            adapted_response["list__phones"] = phones

        if emails := response.get("emails"):
            adapted_response["list__emails"] = emails

        return [adapted_response]
