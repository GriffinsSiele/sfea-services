from fastapi import FastAPI
from api.v1.endpoints.phone_service import router as phone_router
from api.v1.endpoints.email_service import router as email_router
from api.v1.endpoints.aggregator import router as aggregator_router


def mount_routers(app: FastAPI) -> None:
    app.include_router(phone_router)
    app.include_router(email_router)
    app.include_router(aggregator_router)


