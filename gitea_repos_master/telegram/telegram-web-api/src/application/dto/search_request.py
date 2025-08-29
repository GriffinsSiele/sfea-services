from pydantic import BaseModel, Field
from typing import Optional


class SearchRequest(BaseModel):
    """Request DTO for search operations"""
    
    # Only one of these should be provided
    phone: Optional[str] = Field(None, description="Phone number to search for")
    username: Optional[str] = Field(None, description="Username to search for")
    # Optional unified field; if provided, service will auto-detect type via Validator
    value: Optional[str] = Field(None, description="Unified input; phone/email/username for auto-detection")
    
    class Config:
        schema_extra = {
            "example": {
                "phone": "79319999999",
                "username": None,
                "value": None
            }
        }
    
    def validate_request(self) -> bool:
        """Validate that exactly one search parameter is provided"""
        if self.phone and self.username:
            raise ValueError("Cannot provide both phone and username")
        if not self.phone and not self.username and not self.value:
            raise ValueError("Must provide either phone, username, or value")
        return True
