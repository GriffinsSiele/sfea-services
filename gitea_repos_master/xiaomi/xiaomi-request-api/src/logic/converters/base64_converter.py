import base64

from worker_classes.utils import short

from src.interfaces import AbstractBase64Converter

from .base64_exceptions import Base64ConverterException


class Base64Converter(AbstractBase64Converter):
    """
    Конвертирует изображения base64 в bytes.
    """

    @staticmethod
    def covert_to_bytes(base64_string: str) -> bytes:
        """Конвертирует изображение base64 в bytes

        :param base64_string: Изображение в формате base64.
        :response: Изображение в формате bytes.
        """
        if not base64_string:
            raise Base64ConverterException("Empty message passed to base64 converter")
        try:
            return base64.b64decode(base64_string)
        except Exception as e:
            message = f"Error converting base64 to bytes: {short(e)}"
            raise Base64ConverterException(message)
