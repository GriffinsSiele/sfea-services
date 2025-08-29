from isphere_exceptions.success import NoDataEvent
from isphere_exceptions.worker import InternalWorkerError


class DefaultHoleheAdapter:
    @staticmethod
    def cast_holehe_to_isphere(response):
        response = response[0] if response else None

        if not response:
            return response

        if response.get("rateLimit"):
            raise InternalWorkerError("Ошибка внутри использования модуля")

        if not response.get("exists"):
            raise NoDataEvent()

        output = {
            "result": "Найден",
            "result_code": "FOUND",
        }

        if response.get("phoneNumber"):
            output["phone_number"] = response["phoneNumber"]

        if response.get("emailrecovery"):
            output["email_recovery"] = response["emailrecovery"]

        if response.get("others"):
            output.update(response.get("others"))

        return [output]
