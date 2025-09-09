"""
API Router Configuration

This module handles the mounting of all API version routers
onto the main FastAPI application.
"""

from fastapi import FastAPI
from api.v1.endpoints.phone_service import router as phone_router
from api.v1.endpoints.email_service import router as email_router
from api.v1.endpoints.aggregator import router as aggregator_router


def mount_routers(app: FastAPI) -> None:
    """
    Mount all versioned API routers onto the FastAPI app.
    
    This function organizes API endpoints by version and functionality:
    - v1/xiaomi/phone: Phone number parsing
    - v1/xiaomi/email: Email parsing
    - v1/xiaomi: Unified parsing endpoints
    
    Args:
        app: The FastAPI application instance to mount routers on
    """
    # Mount v1 API routers
    app.include_router(phone_router, prefix="/api/v1")
    app.include_router(email_router, prefix="/api/v1")
    app.include_router(aggregator_router, prefix="/api/v1")



