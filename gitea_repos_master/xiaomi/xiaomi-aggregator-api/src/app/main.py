from fastapi import FastAPI

from app.api.v1.controllers import api_router
from app.middleware import add_middlewares


def create_app() -> FastAPI:
    app = FastAPI(title="xiaomi-aggregator-api", version="1.0.0")
    add_middlewares(app)
    app.include_router(api_router, prefix="/api/v1")
    return app


app = create_app()



