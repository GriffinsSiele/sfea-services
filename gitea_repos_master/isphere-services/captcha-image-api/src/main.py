import asyncio
from contextlib import asynccontextmanager
from typing import Any

from fastapi import FastAPI, responses
from fastapi.openapi.docs import get_swagger_ui_html
from starlette import status as starlette_status
from starlette.staticfiles import StaticFiles

from src.common import constant, exceptions, logger
from src.config.api_config import api_settings
from src.db.session import ping_db
from src.logic.nnetworks import nnetwork_service
from src.logic.s3service import s3_service
from src.logic.sentry import Sentry
from src.request_params.api import main_router
from src.schemas import ServerStatusInfo
from src.thread import (
    RouterLoggingMiddleware,
    generic_exception_handler,
    internal_exception_handler,
)


@asynccontextmanager
async def lifespan(app: FastAPI):
    # handle events on startup
    logger.initialize_core_loggers()
    Sentry().create(dsn=api_settings.SENTRY_URL_SERVER, mode=api_settings.MODE)
    asyncio.create_task(nnetwork_service.load_s3_nnetworks(), name="load_s3_nnetworks")
    yield
    # handle events on shutdown


app: FastAPI = FastAPI(
    title=api_settings.PROJECT_NAME,
    docs_url=None,
    redoc_url=None,
    lifespan=lifespan,
)

app.mount("/static", StaticFiles(directory="static"), name="static")

app.include_router(router=main_router, prefix=api_settings.API_PREFIX)

app.add_exception_handler(exceptions.GenericApiException, generic_exception_handler)

app.add_exception_handler(Exception, internal_exception_handler)

app.add_middleware(RouterLoggingMiddleware)


@app.get("/", include_in_schema=False)
def root() -> str:
    return app.title


@app.get("/docs", include_in_schema=False)
async def custom_swagger_ui_html():
    return get_swagger_ui_html(
        openapi_url=app.openapi_url,
        title=app.title + " - Swagger UI",
        oauth2_redirect_url=app.swagger_ui_oauth2_redirect_url,
        swagger_js_url="/static/swagger-ui-bundle.js",
        swagger_css_url="/static/swagger-ui.css",
    )


@app.get(
    "/status",
    response_model=ServerStatusInfo,
    responses={
        starlette_status.HTTP_200_OK: {
            "description": f"Статус сервера '{constant.SERVER_STATUS_OK}' - готов к работе",
            "content": {
                "application/json": {"example": {"status": constant.SERVER_STATUS_OK}}
            },
        },
        starlette_status.HTTP_503_SERVICE_UNAVAILABLE: {
            "description": f"Статус сервера '{constant.SERVER_STATUS_PENDING}' - в процессе формирования данных о доступных методах решения капчи с помощью нейросетей",
            "content": {
                "application/json": {
                    "example": {"status": constant.SERVER_STATUS_PENDING}
                }
            },
        },
    },
    description="Получение статуса текущего состояния сервера",
    tags=["server_status"],
)
async def get_status():
    def _generate_response(
        status: Any,
        status_code: int = starlette_status.HTTP_503_SERVICE_UNAVAILABLE,
    ):
        return responses.JSONResponse(
            status_code=status_code,
            content=ServerStatusInfo.model_validate({"status": status}).model_dump(),
        )

    if "load_s3_nnetworks" in {task.get_name() for task in asyncio.tasks.all_tasks()}:
        return _generate_response(status=constant.SERVER_STATUS_PENDING)

    msg_db_err, msg_s3_err = await asyncio.gather(
        *[
            ping_db(),
            s3_service.ping_bucket(bucket=api_settings.S3_BUCKET_MAIN),
        ]
    )
    if msg_db_err:
        return _generate_response(
            status=msg_db_err, status_code=starlette_status.HTTP_501_NOT_IMPLEMENTED
        )
    elif msg_s3_err:
        return _generate_response(
            status=msg_s3_err, status_code=starlette_status.HTTP_502_BAD_GATEWAY
        )

    return _generate_response(
        status=constant.SERVER_STATUS_OK, status_code=starlette_status.HTTP_200_OK
    )
