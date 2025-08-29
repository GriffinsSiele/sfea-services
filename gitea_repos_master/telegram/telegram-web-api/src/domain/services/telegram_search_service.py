from abc import ABC, abstractmethod
from typing import Optional, List
from ..entities.telegram_user import TelegramUser
from ..entities.telegram_session import TelegramSession
from ..value_objects.phone_number import PhoneNumber
from ..value_objects.username import Username
from ..repositories.telegram_user_repository import TelegramUserRepository
from ..repositories.telegram_session_repository import TelegramSessionRepository


class TelegramSearchService(ABC):
    """Domain service for Telegram search operations"""
    
    def __init__(
        self,
        user_repository: TelegramUserRepository,
        session_repository: TelegramSessionRepository
    ):
        self.user_repository = user_repository
        self.session_repository = session_repository
    
    @abstractmethod
    async def search_by_phone(
        self, 
        phone: PhoneNumber, 
        session: TelegramSession
    ) -> List[TelegramUser]:
        """Search for users by phone number"""
        pass
    
    @abstractmethod
    async def search_by_username(
        self, 
        username: Username, 
        session: TelegramSession
    ) -> Optional[TelegramUser]:
        """Search for user by username"""
        pass
    
    @abstractmethod
    async def validate_session_for_search(self, session: TelegramSession) -> bool:
        """Validate if session can perform search operation"""
        pass
    
    @abstractmethod
    async def update_session_after_search(self, session: TelegramSession) -> None:
        """Update session after successful search"""
        pass
    
    @abstractmethod
    async def get_available_session(self) -> Optional[TelegramSession]:
        """Get an available session for search operations"""
        pass

