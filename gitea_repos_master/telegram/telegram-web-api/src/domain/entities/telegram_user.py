from dataclasses import dataclass
from typing import Optional, List
from datetime import datetime, timezone


@dataclass
class TelegramUser:
    """Domain entity representing a Telegram user"""
    id: int
    username: Optional[str] = None
    first_name: Optional[str] = None
    last_name: Optional[str] = None
    phone: Optional[str] = None
    is_bot: bool = False
    is_verified: bool = False
    is_restricted: bool = False
    is_scam: bool = False
    is_fake: bool = False
    access_hash: Optional[int] = None
    photo: Optional[str] = None
    status: Optional[str] = None
    created_at: Optional[datetime] = None
    updated_at: Optional[datetime] = None
    
    def __post_init__(self):
        if not self.created_at:
            self.created_at = datetime.now(timezone.utc)
        if not self.updated_at:
            self.updated_at = datetime.now(timezone.utc)
    
    def is_active(self) -> bool:
        """Check if user is active and not restricted"""
        return not (self.is_restricted or self.is_scam or self.is_fake)
    
    def get_display_name(self) -> str:
        """Get user's display name"""
        if self.first_name and self.last_name:
            return f"{self.first_name} {self.last_name}"
        elif self.first_name:
            return self.first_name
        elif self.username:
            return f"@{self.username}"
        else:
            return f"User {self.id}"
    
    def to_dict(self) -> dict:
        """Convert entity to dictionary"""
        return {
            "id": self.id,
            "username": self.username,
            "first_name": self.first_name,
            "last_name": self.last_name,
            "phone": self.phone,
            "is_bot": self.is_bot,
            "is_verified": self.is_verified,
            "is_restricted": self.is_restricted,
            "is_scam": self.is_scam,
            "is_fake": self.is_fake,
            "access_hash": self.access_hash,
            "photo": self.photo,
            "status": self.status,
            "created_at": self.created_at.isoformat(),
            "updated_at": self.updated_at.isoformat()
        }
