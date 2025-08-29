import logging
from contextlib import asynccontextmanager

from starlette.staticfiles import StaticFiles

from fastapi import FastAPI
from fastapi.openapi.docs import get_swagger_ui_html
from src.fastapi.adapters import ResponseAdapter
from src.fastapi.router import rsa_router
from src.logic.handler.before_start import before_start


@asynccontextmanager
async def startup(app: FastAPI):
    before_start()
    yield


app = FastAPI(title="rsa-policy-web-api", docs_url=None, lifespan=startup)


@app.get("/", include_in_schema=False)
def service_name():
    """Возвращает название сервиса."""
    return ResponseAdapter.success(app.title)


app.mount("/static", StaticFiles(directory="static"), name="static")


@app.get("/docs", include_in_schema=False)
def custom_swagger_ui_html():
    return get_swagger_ui_html(
        openapi_url=app.openapi_url,
        title=app.title + " - Swagger UI",
        oauth2_redirect_url=app.swagger_ui_oauth2_redirect_url,
        swagger_js_url="/static/swagger-ui-bundle.js",
        swagger_css_url="/static/swagger-ui.css",
    )


app.include_router(rsa_router)
