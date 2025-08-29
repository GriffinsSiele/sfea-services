"""
Модель содержит переопределенный класс FastAPI
"""

from fastapi import FastAPI


class XiaomiFastAPI(FastAPI):
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
