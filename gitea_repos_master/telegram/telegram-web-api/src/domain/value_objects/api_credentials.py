from dataclasses import dataclass
from typing import Optional


@dataclass(frozen=True)
class APICredentials:
    """Value object representing Telegram API credentials"""
    api_id: int
    api_hash: str
    
    def __post_init__(self):
        if not self._is_valid_api_id(self.api_id):
            raise ValueError(f"Invalid API ID: {self.api_id}")
        if not self._is_valid_api_hash(self.api_hash):
            raise ValueError(f"Invalid API hash format: {self.api_hash}")
    
    @staticmethod
    def _is_valid_api_id(api_id: int) -> bool:
        """Validate API ID format"""
        return isinstance(api_id, int) and api_id > 0
    
    @staticmethod
    def _is_valid_api_hash(api_hash: str) -> bool:
        """Validate API hash format"""
        if not isinstance(api_hash, str):
            return False
        
        # API hash should be 32 characters long and contain only hex characters
        if len(api_hash) != 32:
            return False
        
        try:
            int(api_hash, 16)
            return True
        except ValueError:
            return False
    
    def __str__(self) -> str:
        return f"API ID: {self.api_id}, Hash: {self.api_hash[:8]}..."
    
    def __repr__(self) -> str:
        return f"APICredentials(api_id={self.api_id}, api_hash='{self.api_hash[:8]}...')"
    
    def __eq__(self, other) -> bool:
        if not isinstance(other, APICredentials):
            return False
        return self.api_id == other.api_id and self.api_hash == other.api_hash
    
    def __hash__(self) -> int:
        return hash((self.api_id, self.api_hash))

