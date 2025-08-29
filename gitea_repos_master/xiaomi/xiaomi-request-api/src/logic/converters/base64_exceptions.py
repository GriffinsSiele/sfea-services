class Base64ConverterException(Exception):
    """Исключение при конвертировании изображений base64 в bytes"""

    def __init__(self, message="Base64Converter raise exception"):
        self.message = message
        super().__init__(self.message)
