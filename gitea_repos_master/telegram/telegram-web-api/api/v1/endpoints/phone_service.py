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


router = APIRouter(prefix="/telegram", tags=["telegram"])


@router.post(
    "/search", 
    response_model=Union[SearchResponse, ErrorResponse],
    summary="Search Telegram User",
    description="Search for a Telegram user by phone number or username",
    response_description="User search results or error information"
)
async def search(
    request: SearchRequest, 
    controller: TelegramController = Depends(get_telegram_controller)
) -> Union[SearchResponse, ErrorResponse]:
    """
    Search for a Telegram user by phone number or username.
    
    This endpoint allows you to search for Telegram users using either:
    - Phone number (in various formats)
    - Username (with or without @)
    
    Args:
        request: Search request containing either phone or username
        controller: Telegram controller dependency
        
    Returns:
        SearchResponse: Successful search results with user data
        ErrorResponse: Error information if search fails
        
    Raises:
        HTTPException: For various error conditions
    """
    return await controller.search_user(request)


@router.get(
    "/health",
    summary="Health Check",
    description="Check the health status of the Telegram API service",
    response_description="Service health information"
)
async def health():
    """
    Health check endpoint for the Telegram API service.
    
    Returns the current status, version, and timestamp of the service.
    This endpoint is used by load balancers and monitoring systems
    to verify the service is running and responsive.
    
    Returns:
        dict: Health status information including:
            - status: Service status (healthy/unhealthy)
            - service: Service name
            - version: API version
            - timestamp: Current UTC timestamp
    """
    from datetime import datetime, timezone

    return {
        "status": "healthy",
        "service": "telegram-api",
        "version": "1.0.0",
        "timestamp": datetime.now(timezone.utc).isoformat(),
    }


@router.get(
    "/",
    summary="API Information",
    description="Get information about the Telegram Search API",
    response_description="API information and available endpoints"
)
async def root():
    """
    Root endpoint providing API information and available endpoints.
    
    Returns basic information about the Telegram Search API including
    service name, version, description, and available endpoints.
    
    Returns:
        dict: API information including:
            - service: Service name
            - version: API version
            - description: Service description
            - endpoints: Available API endpoints
    """
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


