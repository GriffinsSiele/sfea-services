from fastapi.encoders import jsonable_encoder
from fastapi.requests import Request
from starlette.responses import JSONResponse

from src.common.exceptions import GenericApiException
from src.common.logger import Logger
from src.schemas import SimpleApiError

logger = Logger("Exceptions").get_logger()


async def generic_exception_handler(request: Request, exc: GenericApiException):
    logger.info(f"{type(exc)} - {exc.detail}")
    return JSONResponse(
        status_code=exc.status_code,
        content=SimpleApiError.model_validate(exc).model_dump(),
    )


async def internal_exception_handler(request: Request, exc: Exception):
    return JSONResponse(
        status_code=500,
        content=jsonable_encoder(
            {
                "message": exc.message if hasattr(exc, "message") else exc.__str__(),
                "error": str(type(exc)),
            }
        ),
    )
