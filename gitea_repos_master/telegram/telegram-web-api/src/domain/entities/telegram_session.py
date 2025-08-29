from dataclasses import dataclass
from typing import Optional, List
from datetime import datetime, timedelta, timezone


@dataclass
class TelegramSession:
    """Domain entity representing a Telegram session"""
    id: str
    auth_key: str
    api_id: int
    api_hash: str
    is_active: bool = True
    search_count: int = 0
    max_searches_per_day: int = 29
    password: Optional[str] = None
    proxy_id: Optional[str] = None
    last_message: Optional[datetime] = None
    friends: Optional[List[str]] = None
    created_at: Optional[datetime] = None
    updated_at: Optional[datetime] = None
    
    def __post_init__(self):
        if not self.created_at:
            self.created_at = datetime.now(timezone.utc)
        if not self.updated_at:
            self.updated_at = datetime.now(timezone.utc)
        if self.friends is None:
            self.friends = []
        if self.search_count is None:
            self.search_count = 0
    
    def can_perform_search(self) -> bool:
        """Check if session can perform another search today"""
        if not self.is_active:
            return False
        
        # Reset search count if it's a new day
        if self.last_message and self.last_message.astimezone(timezone.utc).date() < datetime.now(timezone.utc).date():
            self.search_count = 0
            return True
        
        return self.search_count < self.max_searches_per_day
    
    def increment_search_count(self):
        """Increment search count and update last message time"""
        self.search_count += 1
        self.last_message = datetime.now(timezone.utc)
        self.updated_at = datetime.now(timezone.utc)
    
    def add_friend(self, friend_id: str):
        """Add a friend to the session"""
        if friend_id not in self.friends:
            self.friends.append(friend_id)
            self.updated_at = datetime.now(timezone.utc)
    
    def remove_friend(self, friend_id: str):
        """Remove a friend from the session"""
        if friend_id in self.friends:
            self.friends.remove(friend_id)
            self.updated_at = datetime.now(timezone.utc)
    
    def get_next_use_time(self) -> datetime:
        """Calculate when this session can be used again"""
        if self.search_count >= self.max_searches_per_day:
            # Calculate time until next day
            tomorrow = datetime.now(timezone.utc).replace(hour=0, minute=0, second=0, microsecond=0) + timedelta(days=1)
            return tomorrow
        return datetime.now(timezone.utc)
    
    def to_dict(self) -> dict:
        """Convert entity to dictionary"""
        return {
            "id": self.id,
            "auth_key": self.auth_key,
            "api_id": self.api_id,
            "api_hash": self.api_hash,
            "password": self.password,
            "proxy_id": self.proxy_id,
            "last_message": self.last_message.isoformat() if self.last_message else None,
            "friends": self.friends,
            "is_active": self.is_active,
            "search_count": self.search_count,
            "max_searches_per_day": self.max_searches_per_day,
            "created_at": self.created_at.isoformat(),
            "updated_at": self.updated_at.isoformat()
        }
