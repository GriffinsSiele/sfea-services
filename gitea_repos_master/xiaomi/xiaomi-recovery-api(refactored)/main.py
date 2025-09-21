"""
Xiaomi Web API

A FastAPI-based service for parsing phone numbers and emails using Xiaomi service.
This service provides both individual parsing capabilities and batch aggregation
for multiple inputs with automatic type detection.

Features:
- Parse phone numbers and email addresses
- Batch processing with automatic input type detection
- Integration with Validator service for input validation
- Comprehensive error handling and logging
- Health check endpoints for monitoring
"""

import logging
from contextlib import asynccontextmanager
from datetime import datetime, timezone

from fastapi import FastAPI, Request
from fastapi.middleware.cors import CORSMiddleware
from fastapi.middleware.trustedhost import TrustedHostMiddleware
from fastapi.responses import JSONResponse
from starlette.exceptions import HTTPException as StarletteHTTPException

from api.router import mount_routers
from api.middleware import add_middlewares
from core.settings import get_settings
import sentry_sdk


logging.basicConfig(level=logging.INFO, format="%(asctime)s - %(name)s - %(levelname)s - %(message)s")
logger = logging.getLogger(__name__)
settings = get_settings()

# Optional Sentry initialization
if settings.sentry_dsn:
    sentry_sdk.init(dsn=settings.sentry_dsn, traces_sample_rate=0.0)


@asynccontextmanager
async def lifespan(app: FastAPI):
    """
    Application lifespan manager.
    
    Handles startup and shutdown events for the FastAPI application.
    This includes logging configuration, database connections, and
    cleanup operations.
    
    Args:
        app: The FastAPI application instance
        
    Yields:
        None: Control back to the application during its lifetime
    """
    logger.info("Starting Xiaomi Parsing API ...")
    logger.info(f"Environment: {settings.mode}")
    yield
    logger.info("Shutting down Xiaomi Parsing API ...")


app = FastAPI(
    title="Xiaomi Parsing API",
    description="Unified parsing API for phones and emails (Xiaomi scope)",
    version="0.1.0",
    docs_url="/docs" if settings.mode != "production" else None,
    redoc_url="/redoc" if settings.mode != "production" else None,
    lifespan=lifespan,
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

app.add_middleware(TrustedHostMiddleware, allowed_hosts=["*"])


@app.exception_handler(StarletteHTTPException)
async def http_exception_handler(request: Request, exc: StarletteHTTPException):
    return JSONResponse(
        status_code=exc.status_code,
        content={
            "success": False,
            "error": exc.detail,
            "error_code": f"HTTP_{exc.status_code}",
            "timestamp": datetime.now(timezone.utc).isoformat(),
        },
    )


@app.exception_handler(Exception)
async def general_exception_handler(request: Request, exc: Exception):
    logger.error(f"Unhandled exception: {exc}")
    return JSONResponse(
        status_code=500,
        content={
            "success": False,
            "error": "Internal server error",
            "error_code": "INTERNAL_ERROR",
            "timestamp": datetime.now(timezone.utc).isoformat(),
        },
    )


mount_routers(app)
add_middlewares(app)


@app.get(
    "/",
    summary="API Information",
    description="Get information about the Xiaomi Parsing API",
    response_description="API information and available endpoints"
)
async def root():
    """
    Root endpoint providing API information and available endpoints.
    
    Returns basic information about the Xiaomi Parsing API including
    service name, version, description, and available endpoints.
    
    Returns:
        dict: API information including:
            - service: Service name
            - version: API version
            - status: Current service status
            - environment: Current environment mode
            - endpoints: Available API endpoints
    """
    return {
        "service": "Xiaomi Parsing API",
        "version": "0.1.0",
        "status": "running",
        "environment": settings.mode,
        "endpoints": {
            "unified_parse": "/api/v1/xiaomi/parse",
            "phone_parse": "/api/v1/xiaomi/phone/parse",
            "email_parse": "/api/v1/xiaomi/email/parse",
        },
    }


@app.get(
    "/health",
    summary="Health Check",
    description="Check the health status of the Xiaomi Parsing API service",
    response_description="Service health information"
)
async def health():
    """
    Health check endpoint for the Xiaomi Parsing API service.
    
    Returns the current status, version, and timestamp of the service.
    This endpoint is used by load balancers and monitoring systems
    to verify the service is running and responsive.
    
    Returns:
        dict: Health status information including:
            - status: Service status (healthy/unhealthy)
            - service: Service name
            - version: API version
    """
    return {"status": "healthy", "service": "xiaomi-web-api", "version": "0.1.0"}


