from typing import Union

from fastapi import APIRouter, Depends

from api.v1.schemas.service_schemas import (
    SearchRequest,
    SearchResponse,
    ErrorResponse,
)
from src.interface.controllers.telegram_controller import (
    TelegramController,
    get_telegram_controller,
)


router = APIRouter(prefix="/api/v1/telegram", tags=["telegram"])


@router.post("/search", response_model=Union[SearchResponse, ErrorResponse])
async def search(request: SearchRequest, controller: TelegramController = Depends(get_telegram_controller)) -> Union[SearchResponse, ErrorResponse]:
    return await controller.search_user(request)


@router.get("/health")
async def health():
    from datetime import datetime, timezone

    return {
        "status": "healthy",
        "service": "telegram-api",
        "version": "1.0.0",
        "timestamp": datetime.now(timezone.utc).isoformat(),
    }


@router.get("/")
async def root():
    return {
        "service": "Telegram Search API",
        "version": "1.0.0",
        "description": "Unified API for searching Telegram users by phone or username",
        "endpoints": {
            "search": "/api/v1/telegram/search",
            "health": "/api/v1/telegram/health",
            "docs": "/docs",
        },
    }


