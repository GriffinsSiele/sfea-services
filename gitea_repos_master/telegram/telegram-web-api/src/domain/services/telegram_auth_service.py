from abc import ABC, abstractmethod
from typing import Optional
from ..entities.telegram_session import TelegramSession
from ..value_objects.api_credentials import APICredentials


class TelegramAuthService(ABC):
    """Domain service for Telegram authentication operations"""
    
    @abstractmethod
    async def authenticate_session(
        self, 
        session: TelegramSession
    ) -> bool:
        """Authenticate a Telegram session"""
        pass
    
    @abstractmethod
    async def validate_credentials(
        self, 
        credentials: APICredentials
    ) -> bool:
        """Validate API credentials"""
        pass
    
    @abstractmethod
    async def refresh_session(
        self, 
        session: TelegramSession
    ) -> Optional[TelegramSession]:
        """Refresh an expired session"""
        pass
    
    @abstractmethod
    async def revoke_session(
        self, 
        session: TelegramSession
    ) -> bool:
        """Revoke a session"""
        pass
    
    @abstractmethod
    async def is_session_valid(
        self, 
        session: TelegramSession
    ) -> bool:
        """Check if session is still valid"""
        pass

