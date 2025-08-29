from pydantic import BaseModel, Field
from typing import Optional, List, Dict, Any


class SearchResponse(BaseModel):
    """Response DTO for search operations"""
    
    success: bool = Field(..., description="Whether the search was successful")
    data: Optional[List[Dict[str, Any]]] = Field(None, description="Search results")
    errors: List[str] = Field(default_factory=list, description="List of errors if any")
    metadata: Optional[Dict[str, Any]] = Field(None, description="Additional metadata about the search")
    
    class Config:
        schema_extra = {
            "example": {
                "success": True,
                "data": [
                    {
                        "id": 123456789,
                        "username": "testuser",
                        "first_name": "Test",
                        "last_name": "User",
                        "phone": "79319999999",
                        "is_bot": False,
                        "is_verified": False,
                        "is_restricted": False,
                        "is_scam": False,
                        "is_fake": False,
                        "access_hash": 12345678901234567890,
                        "photo": None,
                        "status": "online",
                        "created_at": "2024-01-01T00:00:00",
                        "updated_at": "2024-01-01T00:00:00"
                    }
                ],
                "errors": [],
                "metadata": {
                    "search_type": "phone",
                    "query": "79319999999",
                    "results_count": 1,
                    "session_id": "session_123"
                }
            }
        }

