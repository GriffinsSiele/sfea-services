import logging
from datetime import datetime, timezone
from typing import Union
from telethon.errors import (
    FloodWaitError,
    PhoneNumberInvalidError,
    UsernameInvalidError,
    UsernameNotOccupiedError,
    SessionPasswordNeededError,
    PhoneCodeInvalidError
)

from src.application.dto.error_response import ErrorResponse

logger = logging.getLogger(__name__)


def handle_telegram_errors(error: Exception) -> ErrorResponse:
    """Handle Telegram-specific errors and convert to standardized response"""
    
    if isinstance(error, FloodWaitError):
        return ErrorResponse(
            success=False,
            error="Rate limited by Telegram",
            error_code="TELEGRAM_RATE_LIMIT",
            details={
                "wait_time": error.seconds,
                "retry_after": error.seconds
            },
            timestamp=datetime.now(timezone.utc).isoformat()
        )
    
    elif isinstance(error, PhoneNumberInvalidError):
        return ErrorResponse(
            success=False,
            error="Invalid phone number format",
            error_code="INVALID_PHONE",
            details={
                "error_type": "phone_validation",
                "message": str(error)
            },
            timestamp=datetime.now(timezone.utc).isoformat()
        )
    
    elif isinstance(error, UsernameInvalidError):
        return ErrorResponse(
            success=False,
            error="Invalid username format",
            error_code="INVALID_USERNAME",
            details={
                "error_type": "username_validation",
                "message": str(error)
            },
            timestamp=datetime.now(timezone.utc).isoformat()
        )
    
    elif isinstance(error, UsernameNotOccupiedError):
        return ErrorResponse(
            success=False,
            error="Username not found",
            error_code="USERNAME_NOT_FOUND",
            details={
                "error_type": "user_search",
                "message": str(error)
            },
            timestamp=datetime.now(timezone.utc).isoformat()
        )
    
    elif isinstance(error, SessionPasswordNeededError):
        return ErrorResponse(
            success=False,
            error="Session requires password",
            error_code="SESSION_PASSWORD_NEEDED",
            details={
                "error_type": "authentication",
                "message": str(error)
            },
            timestamp=datetime.now(timezone.utc).isoformat()
        )
    
    elif isinstance(error, PhoneCodeInvalidError):
        return ErrorResponse(
            success=False,
            error="Invalid phone verification code",
            error_code="INVALID_PHONE_CODE",
            details={
                "error_type": "verification",
                "message": str(error)
            },
            timestamp=datetime.now(timezone.utc).isoformat()
        )
    
    elif isinstance(error, RuntimeError) and "Client not connected" in str(error):
        return ErrorResponse(
            success=False,
            error="Telegram client not connected",
            error_code="CLIENT_NOT_CONNECTED",
            details={
                "error_type": "connection",
                "message": str(error)
            },
            timestamp=datetime.now(timezone.utc).isoformat()
        )
    
    else:
        # Log unexpected errors
        logger.error(f"Unexpected error: {type(error).__name__}: {error}")
        
        return ErrorResponse(
            success=False,
            error="Internal server error",
            error_code="INTERNAL_ERROR",
            details={
                "error_type": "unknown",
                "message": str(error)
            },
            timestamp=datetime.now(datetime.UTC).isoformat()
        )
