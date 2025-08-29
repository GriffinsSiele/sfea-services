import base64


class Base64:
    @staticmethod
    def decode(message):
        return base64.b64decode(message)

    @staticmethod
    def encode(message):
        return base64.b64encode(message).decode()
