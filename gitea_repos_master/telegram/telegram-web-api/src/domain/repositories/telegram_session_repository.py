from abc import ABC, abstractmethod
from typing import Optional, List
from ..entities.telegram_session import TelegramSession


class TelegramSessionRepository(ABC):
    """Repository interface for Telegram session operations"""
    
    @abstractmethod
    async def save(self, session: TelegramSession) -> TelegramSession:
        """Save or update a Telegram session"""
        pass
    
    @abstractmethod
    async def find_by_id(self, session_id: str) -> Optional[TelegramSession]:
        """Find session by ID"""
        pass
    
    @abstractmethod
    async def find_by_auth_key(self, auth_key: str) -> Optional[TelegramSession]:
        """Find session by auth key"""
        pass
    
    @abstractmethod
    async def find_available_session(self) -> Optional[TelegramSession]:
        """Find an available session for search operations"""
        pass
    
    @abstractmethod
    async def find_sessions_by_proxy(self, proxy_id: str) -> List[TelegramSession]:
        """Find all sessions using a specific proxy"""
        pass
    
    @abstractmethod
    async def find_all(self, limit: int = 100, offset: int = 0) -> List[TelegramSession]:
        """Find all sessions with pagination"""
        pass
    
    @abstractmethod
    async def delete(self, session_id: str) -> bool:
        """Delete a session by ID"""
        pass
    
    @abstractmethod
    async def exists(self, session_id: str) -> bool:
        """Check if session exists"""
        pass
    
    @abstractmethod
    async def count(self) -> int:
        """Get total count of sessions"""
        pass
    
    @abstractmethod
    async def count_active(self) -> int:
        """Get count of active sessions"""
        pass
    
    @abstractmethod
    async def update_search_count(self, session_id: str, new_count: int) -> bool:
        """Update search count for a session"""
        pass

