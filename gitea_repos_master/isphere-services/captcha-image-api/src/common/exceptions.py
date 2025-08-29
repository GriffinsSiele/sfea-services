from typing import Optional

from fastapi.exceptions import HTTPException
from starlette import status


class GenericApiException(HTTPException):
    message: str = "Generic Error"
    status_code: int = status.HTTP_500_INTERNAL_SERVER_ERROR

    def __init__(
        self,
        message: Optional[str] = None,
        status_code: Optional[int] = None,
        *args,
    ):
        if message is not None:
            self.message = message
        if status_code is not None:
            self.status_code = status_code
        super().__init__(self.status_code, self.message, *args)


class NotFoundException(GenericApiException):
    status_code: int = status.HTTP_400_BAD_REQUEST
    message: str = "Object Not Found Error"


class BadRequestException(GenericApiException):
    status_code: int = status.HTTP_400_BAD_REQUEST
    message: str = "Bad Request"


class AlreadyExistError(GenericApiException):
    status_code: int = status.HTTP_400_BAD_REQUEST
    message: str = "Already Exist"


class ImageValidationError(GenericApiException):
    status_code: int = status.HTTP_400_BAD_REQUEST
    message: str = "Image validation error"


class ValidationError(GenericApiException):
    status_code: int = status.HTTP_400_BAD_REQUEST
    message: str = "Validation error"
