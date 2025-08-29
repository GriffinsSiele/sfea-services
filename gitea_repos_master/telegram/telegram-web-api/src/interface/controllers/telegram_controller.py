import logging
from datetime import datetime, timezone
from typing import Union, Any
from fastapi import APIRouter, Depends, HTTPException, status
from fastapi.responses import JSONResponse

from src.application.dto.search_request import SearchRequest
from src.application.dto.search_response import SearchResponse
from src.application.dto.error_response import ErrorResponse
from src.application.use_cases.search_user_by_phone import SearchUserByPhoneUseCase
from src.application.use_cases.search_user_by_username import SearchUserByUsernameUseCase
from src.interface.middleware.validation import validate_search_request
from src.interface.middleware.rate_limiting import RateLimiter
from src.interface.middleware.error_handling import handle_telegram_errors
from src.infrastructure.services.in_memory_search_service import InMemoryTelegramSearchService
from src.infrastructure.services.telethon_search_service import TelethonTelegramSearchService
from src.domain.repositories.telegram_user_repository import TelegramUserRepository
from src.domain.repositories.telegram_session_repository import TelegramSessionRepository
from src.domain.value_objects.api_credentials import APICredentials
from src.config.settings import get_settings
from src.infrastructure.validator.client import ValidatorClient


def TelegramSearchService():  # provider for DI; tests may override
    settings = get_settings()
    if getattr(settings, "TELEGRAM_LIVE", False):
        # Build Telethon service from settings and optional proxy
        proxy = None
        if settings.PROXY_URL:
            # Minimal parsing: aiohttp/telethon accept dict; leaving URL as-is if library supports string
            proxy = {"url": settings.PROXY_URL}
        credentials = APICredentials(api_id=int(settings.TELEGRAM_API_ID), api_hash=str(settings.TELEGRAM_API_HASH))
        # Repositories can be no-op or in-memory for now
        user_repo = TelegramUserRepository()
        session_repo = TelegramSessionRepository()
        return TelethonTelegramSearchService(
            user_repository=user_repo,
            session_repository=session_repo,
            credentials=credentials,
            auth_key=str(settings.TELEGRAM_AUTH_KEY or ""),
            proxy=proxy,
        )
    return InMemoryTelegramSearchService()


router = APIRouter(prefix="/api/v1/telegram", tags=["telegram"])
logger = logging.getLogger(__name__)


class TelegramController:
    """Unified controller for Telegram operations"""
    
    def __init__(
        self,
        search_service: TelegramSearchService,
        rate_limiter: RateLimiter
    ):
        self.search_service = search_service
        self.rate_limiter = rate_limiter
        self.phone_use_case = SearchUserByPhoneUseCase(search_service)
        self.username_use_case = SearchUserByUsernameUseCase(search_service)
    
    async def search_user(
        self, 
        request: SearchRequest
    ) -> Union[SearchResponse, ErrorResponse]:
        """Unified search endpoint that routes based on data type"""
        try:
            # Validate request
            validation_result = validate_search_request(request)
            if not validation_result["valid"]:
                return ErrorResponse(
                    success=False,
                    error=validation_result["error"],
                    error_code="VALIDATION_ERROR",
                    details=validation_result["details"],
                    timestamp=datetime.now(timezone.utc).isoformat()
                )
            
            # Check rate limiting
            if not await self.rate_limiter.check_rate_limit():
                return ErrorResponse(
                    success=False,
                    error="Rate limit exceeded",
                    error_code="RATE_LIMIT_EXCEEDED",
                    details={"retry_after": await self.rate_limiter.get_retry_after()},
                    timestamp=datetime.now(timezone.utc).isoformat()
                )
            
            # Optional: Validate type via SMK-RK Validator if enabled
            settings = get_settings()
            if getattr(settings, "VALIDATOR_ENABLED", False):
                validator = ValidatorClient()
                # Unified value path
                if request.value:
                    detection = await validator.detect_with_meta(request.value)
                    detected_type = detection.get("type")
                    if detected_type == "phone":
                        request.phone = request.value
                    elif detected_type == "username":
                        request.username = request.value
                    else:
                        return ErrorResponse(
                            success=False,
                            error="Unsupported or unknown value type",
                            error_code="VALIDATOR_UNKNOWN_TYPE",
                            details={"detected": detected_type, "confidence": detection.get("confidence")},
                            timestamp=datetime.now(timezone.utc).isoformat()
                        )
                elif request.phone:
                    detection = await validator.detect_with_meta(request.phone)
                    if detection.get("type") != "phone":
                        return ErrorResponse(
                            success=False,
                            error="Validator type mismatch",
                            error_code="VALIDATOR_TYPE_MISMATCH",
                            details={"provided": "phone", "detected": detection.get("type"), "confidence": detection.get("confidence")},
                            timestamp=datetime.now(timezone.utc).isoformat()
                        )
                elif request.username:
                    detection = await validator.detect_with_meta(request.username)
                    if detection.get("type") != "username":
                        return ErrorResponse(
                            success=False,
                            error="Validator type mismatch",
                            error_code="VALIDATOR_TYPE_MISMATCH",
                            details={"provided": "username", "detected": detection.get("type"), "confidence": detection.get("confidence")},
                            timestamp=datetime.now(timezone.utc).isoformat()
                        )
            
            # Route based on data type (using Validator IS logic)
            if request.phone:
                logger.info(f"Searching by phone: {request.phone}")
                return await self.phone_use_case.execute(request)
            elif request.username:
                logger.info(f"Searching by username: {request.username}")
                return await self.username_use_case.execute(request)
            else:
                return ErrorResponse(
                    success=False,
                    error="No search parameter provided",
                    error_code="MISSING_PARAMETER",
                    details={"required": "phone or username"},
                    timestamp=datetime.now(timezone.utc).isoformat()
                )
                
        except Exception as e:
            logger.error(f"Search error: {e}")
            return handle_telegram_errors(e)


# Dependency injection
def get_telegram_controller(
    search_service: Any = Depends(TelegramSearchService),
    rate_limiter: RateLimiter = Depends(RateLimiter)
) -> TelegramController:
    return TelegramController(search_service, rate_limiter)


@router.post("/search", response_model=Union[SearchResponse, ErrorResponse])
async def search_telegram_user(
    request: SearchRequest,
    controller: TelegramController = Depends(get_telegram_controller)
) -> Union[SearchResponse, ErrorResponse]:
    """
    Unified search endpoint for Telegram users.
    
    This endpoint automatically detects the data type (phone or username)
    and routes the request to the appropriate search service.
    
    - **phone**: Phone number to search for (e.g., "79319999999")
    - **username**: Username to search for (e.g., "testuser")
    
    Only one parameter should be provided.
    """
    return await controller.search_user(request)


@router.get("/health")
async def health_check():
    """Health check endpoint"""
    return {"status": "healthy", "service": "telegram-api", "version": "1.0.0", "timestamp": datetime.now(timezone.utc).isoformat()}


@router.get("/")
async def root():
    """Root endpoint with API information"""
    return {
        "service": "Telegram Search API",
        "version": "1.0.0",
        "description": "Unified API for searching Telegram users by phone or username",
        "endpoints": {
            "search": "/api/v1/telegram/search",
            "health": "/api/v1/telegram/health",
            "docs": "/docs"
        }
    }
