from fastapi import FastAPI


class ElPtsFastAPI(FastAPI):
    def openapi(self):
        super().openapi()

        for _, method_item in self.openapi_schema.get("paths").items():
            for _, param in method_item.items():
                responses = param.get("responses")
                # удаляем ответ 422, также можно удалить любой другой ответ
                if "422" in responses:
                    del responses["422"]

        return self.openapi_schema
