from abc import ABC, abstractmethod
from typing import Optional, List
from ..entities.telegram_user import TelegramUser
from ..value_objects.phone_number import PhoneNumber
from ..value_objects.username import Username


class TelegramUserRepository(ABC):
    """Repository interface for Telegram user operations"""
    
    @abstractmethod
    async def save(self, user: TelegramUser) -> TelegramUser:
        """Save or update a Telegram user"""
        pass
    
    @abstractmethod
    async def find_by_id(self, user_id: int) -> Optional[TelegramUser]:
        """Find user by Telegram ID"""
        pass
    
    @abstractmethod
    async def find_by_phone(self, phone: PhoneNumber) -> Optional[TelegramUser]:
        """Find user by phone number"""
        pass
    
    @abstractmethod
    async def find_by_username(self, username: Username) -> Optional[TelegramUser]:
        """Find user by username"""
        pass
    
    @abstractmethod
    async def find_by_access_hash(self, access_hash: int) -> Optional[TelegramUser]:
        """Find user by access hash"""
        pass
    
    @abstractmethod
    async def find_all(self, limit: int = 100, offset: int = 0) -> List[TelegramUser]:
        """Find all users with pagination"""
        pass
    
    @abstractmethod
    async def delete(self, user_id: int) -> bool:
        """Delete a user by ID"""
        pass
    
    @abstractmethod
    async def exists(self, user_id: int) -> bool:
        """Check if user exists"""
        pass
    
    @abstractmethod
    async def count(self) -> int:
        """Get total count of users"""
        pass

