class ResponseAdapter:

    @staticmethod
    def __fill_template(code, status, data):
        return {"code": code, "status": status, "data": data}

    @classmethod
    def success(cls, data):
        response = cls.__fill_template(200, "Success", data)
        return response

    @classmethod
    def media_error(cls, data):
        response = cls.__fill_template(415, "Unsupported_Media", [data])
        return response

    @classmethod
    def validation_error(cls, data):
        response = cls.__fill_template(422, "Validation_Error", [data])
        return response

    @classmethod
    def internal_error(cls, data):
        response = cls.__fill_template(500, "Internal_Error", [data])
        return response
