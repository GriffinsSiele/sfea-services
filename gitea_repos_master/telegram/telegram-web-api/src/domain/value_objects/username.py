import re
from dataclasses import dataclass


@dataclass(frozen=True)
class Username:
    """Value object representing a Telegram username"""
    value: str
    
    def __post_init__(self):
        if not self._is_valid(self.value):
            raise ValueError(f"Invalid username format: {self.value}")
    
    @staticmethod
    def _is_valid(username: str) -> bool:
        """Validate username format"""
        if not username:
            return False
        
        # Username must be 5-32 characters long
        if len(username) < 5 or len(username) > 32:
            return False
        
        # Username can only contain letters, numbers, and underscores
        # Must start with a letter
        if not re.match(r'^[a-zA-Z][a-zA-Z0-9_]*$', username):
            return False
        
        # Username cannot end with underscore
        if username.endswith('_'):
            return False
        
        # Username cannot contain consecutive underscores
        if '__' in username:
            return False
        
        return True
    
    @property
    def normalized(self) -> str:
        """Get normalized username (lowercase)"""
        return self.value.lower()
    
    @property
    def with_at(self) -> str:
        """Get username with @ prefix"""
        return f"@{self.value}"
    
    def __str__(self) -> str:
        return self.value
    
    def __repr__(self) -> str:
        return f"Username('{self.value}')"
    
    def __eq__(self, other) -> bool:
        if not isinstance(other, Username):
            return False
        return self.normalized == other.normalized
    
    def __hash__(self) -> int:
        return hash(self.normalized)

