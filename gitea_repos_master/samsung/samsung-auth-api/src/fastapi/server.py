"""
Модель содержит переопределенный класс FastAPI
"""

import contextvars
import os
import signal

from fastapi import FastAPI

handler_name_contextvar = contextvars.ContextVar("handler", default="auth")


class SamsungFastAPI(FastAPI):
    """
    Переопределенный класс FastAPI, с целью изменить поведение при ошибке валидации данных.
    """

    def openapi(self):
        """Удаляет ответ 422 из openapi.

        :return: Измененная openapi схема.
        """
        super().openapi()
        for _, method_item in self.openapi_schema.get("paths").items():
            for _, param in method_item.items():
                responses = param.get("responses")
                # удаляем ответ 422, также можно удалить любой другой ответ
                if "422" in responses:
                    del responses["422"]

        return self.openapi_schema

    @staticmethod
    async def stop():
        """Останавливает приложение.

        :return: None
        """
        os.kill(os.getpid(), signal.SIGTERM)
