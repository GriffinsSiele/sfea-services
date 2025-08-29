from isphere_exceptions import ISphereException
from starlette import status as starlette_status
from starlette.responses import JSONResponse, Response
from worker_classes.keydb.response_builder import KeyDBResponseBuilder

from fastapi.encoders import jsonable_encoder
from fastapi.requests import Request
from src.schemas import ISphereExceptionSchema


class APIExceptionHandler:

    NO_CONTENT_STATUSES = {
        starlette_status.HTTP_204_NO_CONTENT,
    }

    @classmethod
    async def isphere_exception_handler(
        cls, request: Request, exc: ISphereException
    ) -> JSONResponse | Response:
        if exc.code in cls.NO_CONTENT_STATUSES:
            return Response(status_code=exc.code)
        return JSONResponse(
            status_code=exc.code,
            content=ISphereExceptionSchema.model_validate(
                KeyDBResponseBuilder.error(exc),
            ).model_dump(),
        )

    @classmethod
    async def generic_exception_handler(
        cls, request: Request, exc: Exception
    ) -> JSONResponse:
        return JSONResponse(
            status_code=starlette_status.HTTP_500_INTERNAL_SERVER_ERROR,
            content=jsonable_encoder(KeyDBResponseBuilder.error(exc)),
        )
