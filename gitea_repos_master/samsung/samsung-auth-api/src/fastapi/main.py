"""
Главный модуль, точка входа в приложение.

Для запуска необходимо воспользоваться сервером uvicorn:

`uvicorn src.fastapi.main:app --log-config=./src/config/logging.yaml`

где файл `logging.yaml` содержит настройки логера для uvicorn.
"""

import json
import logging
import warnings

from isphere_exceptions.source import SourceIncorrectDataDetected
from starlette.staticfiles import StaticFiles
from worker_classes.keydb.response_builder import KeyDBResponseBuilder
from worker_classes.logger import Logger
from worker_classes.sentry.sentry import Sentry, sentry_remove_context
from worker_classes.thread.timing import timing
from worker_classes.utils import short_str

from fastapi import Request, status
from fastapi.exceptions import RequestValidationError
from fastapi.openapi.docs import get_swagger_ui_html
from fastapi.responses import JSONResponse
from src.config import settings
from src.fastapi.router import samsung_router
from src.fastapi.schemas import SamsungSearchResponse
from src.fastapi.server import SamsungFastAPI
from src.fastapi.storage import lifespan
from src.logger.logger_adapter import request_id_contextvar

Logger().create()
Sentry(
    settings.SENTRY_URL, settings.MODE, custom_log_formatter=sentry_remove_context
).create()
warnings.filterwarnings("ignore", category=RuntimeWarning)


app = SamsungFastAPI(title="samsung-auth-api", docs_url=None, lifespan=lifespan)
app.mount("/static", StaticFiles(directory="static"), name="static")

app.include_router(samsung_router)


logging.info("Worker started")


@app.get("/docs", include_in_schema=False)
async def custom_swagger_ui_html():
    return get_swagger_ui_html(
        openapi_url=app.openapi_url,
        title=app.title + " - Swagger UI",
        oauth2_redirect_url=app.swagger_ui_oauth2_redirect_url,
        swagger_js_url="/static/swagger-ui-bundle.js",
        swagger_css_url="/static/swagger-ui.css",
    )


@app.get("/", include_in_schema=False)
async def service_name():
    """Возвращает название сервиса."""
    return app.title


@app.exception_handler(RequestValidationError)
async def validation_exception_handler(
    request: Request, exc: RequestValidationError
) -> JSONResponse:
    """Обработчик исключений ошибок валидации.

    :param request: Запрос.
    :param exc: Исключение.
    :return: Ответ в формате JSONResponse.
    """
    return await prepare_not_found_response(exc)


@timing("API elapsed time")
async def prepare_not_found_response(exc: RequestValidationError) -> JSONResponse:
    """Формирует ответ JSONResponse при ошибке валидации.
    Код вынесен в отдельную функцию с целью применения декоратора timing.

    :param exc:  Исключение.
    :return:  Ответ в формате JSONResponse.
    """
    payload = exc.errors()[0].get("input")
    logging.info(f'Start search for payload: "{payload}"')
    source_incorrect_data = SamsungSearchResponse(
        **KeyDBResponseBuilder.error(SourceIncorrectDataDetected())
    )
    result = JSONResponse(
        status_code=status.HTTP_505_HTTP_VERSION_NOT_SUPPORTED,
        content=source_incorrect_data.model_dump(),
    )
    logging.info(f'Found: "{short_str(json.loads(result.body))}"')
    return result


@app.exception_handler(Exception)
async def common_error_handler(request: Request, exc: Exception) -> JSONResponse:
    """Обработчик исключений FastAPI. Возвращает ответ в формате JSONResponse.

    :param request: Запрос.
    :param exc: Исключение.
    :return: Ответ в формате JSONResponse.
    """
    return await common_error(exc)


@timing("API elapsed time")
async def common_error(e: Exception) -> JSONResponse:
    """Формирует ответ JSONResponse при ошибках, возникших вне обработчика исключений.
    Код вынесен в отдельную функцию с целью применения декоратора timing.

    :param e:  Исключение.
    :return:  Ответ в формате JSONResponse.
    """
    payload = request_id_contextvar.get()
    logging.info(f'Start search for payload: "{payload}"')
    logging.warning(e)
    internal_error = SamsungSearchResponse(**KeyDBResponseBuilder.error(e))
    result = JSONResponse(
        status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
        content=internal_error.model_dump(),
    )
    logging.info(f'Found: "{short_str(json.loads(result.body))}"')
    return result
