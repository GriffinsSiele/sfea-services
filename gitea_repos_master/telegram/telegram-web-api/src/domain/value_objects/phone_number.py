import re
from typing import Optional
from dataclasses import dataclass


@dataclass(frozen=True)
class PhoneNumber:
    """Value object representing a phone number"""
    value: str
    country_code: Optional[str] = None
    
    def __post_init__(self):
        if not self._is_valid(self.value):
            raise ValueError(f"Invalid phone number format: {self.value}")
    
    @staticmethod
    def _is_valid(phone: str) -> bool:
        """Validate phone number format"""
        # Remove all non-digit characters
        digits_only = re.sub(r'\D', '', phone)
        
        # Check if it's a valid length (7-15 digits)
        if len(digits_only) < 7 or len(digits_only) > 15:
            return False
        
        # Check if it starts with a valid country code or local format
        if digits_only.startswith('0') and len(digits_only) >= 10:
            return True
        if digits_only.startswith('1') and len(digits_only) >= 10:
            return True
        if digits_only.startswith('7') and len(digits_only) >= 10:
            return True
        
        return True
    
    @property
    def digits_only(self) -> str:
        """Get phone number with only digits"""
        return re.sub(r'\D', '', self.value)
    
    @property
    def formatted(self) -> str:
        """Get formatted phone number"""
        digits = self.digits_only
        
        if digits.startswith('7') and len(digits) == 11:
            return f"+7 ({digits[1:4]}) {digits[4:7]}-{digits[7:9]}-{digits[9:11]}"
        elif digits.startswith('1') and len(digits) == 11:
            return f"+1 ({digits[1:4]}) {digits[4:7]}-{digits[7:11]}"
        else:
            return digits
    
    def __str__(self) -> str:
        return self.value
    
    def __repr__(self) -> str:
        return f"PhoneNumber('{self.value}')"
    
    def __eq__(self, other) -> bool:
        if not isinstance(other, PhoneNumber):
            return False
        return self.digits_only == other.digits_only
    
    def __hash__(self) -> int:
        return hash(self.digits_only)

