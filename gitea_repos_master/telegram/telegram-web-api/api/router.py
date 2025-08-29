from fastapi import FastAPI
from api.v1.endpoints.phone_service import router as phone_router
from api.v1.endpoints.aggregator import router as aggregator_router


def mount_routers(app: FastAPI) -> None:
    """Mount all versioned API routers onto the FastAPI app."""
    # Include compatibility router that delegates to Telegram controller
    app.include_router(phone_router)
    app.include_router(aggregator_router)


