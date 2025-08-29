from pydantic import BaseModel, Field
from typing import Optional, Dict, Any


class ErrorResponse(BaseModel):
    """Error response DTO"""
    
    success: bool = Field(False, description="Always false for error responses")
    error: str = Field(..., description="Error message")
    error_code: Optional[str] = Field(None, description="Error code for programmatic handling")
    details: Optional[Dict[str, Any]] = Field(None, description="Additional error details")
    timestamp: str = Field(..., description="ISO timestamp of when the error occurred")
    
    class Config:
        schema_extra = {
            "example": {
                "success": False,
                "error": "Invalid phone number format",
                "error_code": "INVALID_PHONE",
                "details": {
                    "field": "phone",
                    "value": "invalid_phone",
                    "expected_format": "7XXXXXXXXXX"
                },
                "timestamp": "2024-01-01T00:00:00Z"
            }
        }

