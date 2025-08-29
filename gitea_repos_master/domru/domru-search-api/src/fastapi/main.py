from contextlib import asynccontextmanager

from isphere_exceptions import ISphereException
from starlette.staticfiles import StaticFiles
from worker_classes.sentry.sentry import Sentry, sentry_remove_context

from fastapi import FastAPI
from fastapi.openapi.docs import get_swagger_ui_html
from src.config import settings
from src.fastapi.middleware import RouterLoggingMiddleware
from src.fastapi.router import router
from src.logger.context_logger import ContextLogger
from src.logic.proxy import proxy_cache
from src.thread.exception_handler import APIExceptionHandler


@asynccontextmanager
async def lifespan(app: FastAPI):
    # handle events on startup
    ContextLogger.initialize_core_loggers()
    Sentry(
        sentry_url=settings.SENTRY_URL,
        mode=settings.MODE,
        custom_log_formatter=sentry_remove_context,
    ).create()
    await proxy_cache.init_cache()
    yield
    # handle events on shutdown


app: FastAPI = FastAPI(
    title=settings.PROJECT_NAME,
    docs_url=None,
    redoc_url=None,
    lifespan=lifespan,
)

app.mount("/static", StaticFiles(directory="static"), name="static")

app.include_router(router=router)

app.add_exception_handler(Exception, APIExceptionHandler.generic_exception_handler)
app.add_exception_handler(ISphereException, APIExceptionHandler.isphere_exception_handler)

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
    description="Получение статуса состояния сервера",
    tags=["server_status"],
)
async def get_status() -> dict:
    return {"status": "ok"}
