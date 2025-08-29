import logging
import os
from contextlib import asynccontextmanager
from fastapi import FastAPI, Request
from fastapi.middleware.cors import CORSMiddleware
from fastapi.middleware.trustedhost import TrustedHostMiddleware
from fastapi.responses import JSONResponse
from starlette.exceptions import HTTPException as StarletteHTTPException

from api.router import mount_routers
from src.config.settings import get_settings

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(name)s - %(levelname)s - %(message)s"
)
logger = logging.getLogger(__name__)

# Get settings
settings = get_settings()


@asynccontextmanager
async def lifespan(app: FastAPI):
    """Application lifespan manager"""
    # Startup
    logger.info("Starting Telegram Search API...")
    logger.info(f"Environment: {settings.MODE}")
    logger.info(f"MongoDB: {settings.MONGO_URL}")
    logger.info(f"KeyDB: {settings.KEYDB_URL}")
    
    yield
    
    # Shutdown
    logger.info("Shutting down Telegram Search API...")


# Create FastAPI application
app = FastAPI(
    title="Telegram Search API",
    description="Unified API for searching Telegram users by phone or username",
    version="1.0.0",
    docs_url="/docs" if settings.MODE != "production" else None,
    redoc_url="/redoc" if settings.MODE != "production" else None,
    lifespan=lifespan
)

# Add middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Configure appropriately for production
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

app.add_middleware(
    TrustedHostMiddleware,
    allowed_hosts=["*"]  # Configure appropriately for production
)


# Global exception handler
@app.exception_handler(StarletteHTTPException)
async def http_exception_handler(request: Request, exc: StarletteHTTPException):
    """Handle HTTP exceptions"""
    return JSONResponse(
        status_code=exc.status_code,
        content={
            "success": False,
            "error": exc.detail,
            "error_code": f"HTTP_{exc.status_code}",
            "timestamp": "2024-01-01T00:00:00Z"  # Will be replaced with actual timestamp
        }
    )


@app.exception_handler(Exception)
async def general_exception_handler(request: Request, exc: Exception):
    """Handle general exceptions"""
    logger.error(f"Unhandled exception: {exc}")
    return JSONResponse(
        status_code=500,
        content={
            "success": False,
            "error": "Internal server error",
            "error_code": "INTERNAL_ERROR",
            "timestamp": "2024-01-01T00:00:00Z"  # Will be replaced with actual timestamp
        }
    )


# Include routers via compatibility API layer
mount_routers(app)


@app.get("/")
async def root():
    """Root endpoint"""
    return {
        "service": "Telegram Search API",
        "version": "1.0.0",
        "description": "Unified API for searching Telegram users by phone or username",
        "status": "running",
        "environment": settings.MODE,
        "endpoints": {
            "search": "/api/v1/telegram/search",
            "health": "/api/v1/telegram/health",
            "docs": "/docs" if settings.MODE != "production" else "disabled"
        }
    }


@app.get("/health")
async def health_check():
    """Health check endpoint"""
    return {
        "status": "healthy",
        "service": "telegram-api",
        "version": "1.0.0",
        "environment": settings.MODE
    }


if __name__ == "__main__":
    import uvicorn
    
    uvicorn.run(
        "main:app",
        host="0.0.0.0",
        port=int(os.getenv("PORT", 8000)),
        reload=settings.MODE == "development"
    )
