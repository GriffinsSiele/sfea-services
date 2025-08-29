import datetime
import logging

from worker_classes.keydb.interfaces import KeyDBResponse, Records


class KeyDBResponseBuilder:
    @classmethod
    def __base_response(
        cls, code=200, message="ok", status="ok", records=None
    ) -> KeyDBResponse:
        if records is None:
            records = []
        logging.info(f"KeyDB response: [{code}] {message}")
        return {
            "status": status,
            "code": code,
            "message": str(message),
            "records": records,
            "timestamp": int(datetime.datetime.now().timestamp()),
        }

    @staticmethod
    def ok(response: Records) -> KeyDBResponse:
        return KeyDBResponseBuilder.__base_response(200, records=response)

    @staticmethod
    def empty() -> KeyDBResponse:
        return KeyDBResponseBuilder.__base_response(204)

    @staticmethod
    def no_sessions() -> KeyDBResponse:
        return KeyDBResponseBuilder.__base_response(
            512, status="Error", message="Нет активных сессий"
        )

    @staticmethod
    def error(e: Exception, code=500) -> KeyDBResponse:
        return KeyDBResponseBuilder.__base_response(code, status="Error", message=str(e))
