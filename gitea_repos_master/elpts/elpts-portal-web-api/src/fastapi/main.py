import json
import logging

from starlette.staticfiles import StaticFiles
from worker_classes.logger import Logger
from worker_classes.sentry.sentry import Sentry, sentry_remove_context
from worker_classes.thread.timing import timing
from worker_classes.utils import short_str

from fastapi import Request, status
from fastapi.exceptions import RequestValidationError
from fastapi.openapi.docs import get_swagger_ui_html
from fastapi.responses import JSONResponse
from src.config import settings
from src.fastapi.router import elpts_router
from src.fastapi.server import ElPtsFastAPI
from src.logic.adapters import ResponseAdapter

Logger().create()
Sentry(
    settings.SENTRY_URL, settings.MODE, custom_log_formatter=sentry_remove_context
).create()

app = ElPtsFastAPI(
    title="elpts-portal-web-api",
    docs_url=None,
)
app.mount("/static", StaticFiles(directory="static"), name="static")


@app.get("/docs", include_in_schema=False)
async def custom_swagger_ui_html():
    return get_swagger_ui_html(
        openapi_url=app.openapi_url,
        title=app.title + " - Swagger UI",
        oauth2_redirect_url=app.swagger_ui_oauth2_redirect_url,
        swagger_js_url="/static/swagger-ui-bundle.js",
        swagger_css_url="/static/swagger-ui.css",
    )


app.include_router(elpts_router)

response_adapter = ResponseAdapter()

logging.info("Worker started")


@app.get("/", include_in_schema=False)
async def service_name():
    """Возвращает название сервиса."""
    return app.title


@app.exception_handler(RequestValidationError)
async def validation_exception_handler(
    request: Request, exc: RequestValidationError
) -> JSONResponse:
    return await prepare_not_found_response(exc)


@timing("API elapsed time")
async def prepare_not_found_response(exc: RequestValidationError) -> JSONResponse:
    payload = exc.errors()[0].get("input")
    logging.info(f'Start search for payload: "{payload}"')
    source_incorrect_data = response_adapter.error(
        505, "Источник не может выполнить запрос по указанным данным"
    )
    result = JSONResponse(
        status_code=status.HTTP_505_HTTP_VERSION_NOT_SUPPORTED,
        content=source_incorrect_data.model_dump(),
    )
    logging.info(f'Found: "{short_str(json.loads(result.body))}"')
    return result
