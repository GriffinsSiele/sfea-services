from pydantic import BaseModel
from typing import Optional, Any, Dict, List


class XiaomiSearchData(BaseModel):
    """Schema for Xiaomi search data"""
    payload: str
    timeout: Optional[int] = None
    starttime: Optional[int] = None


class XiaomiResponse(BaseModel):
    """Schema for Xiaomi API response"""
    result: str
    result_code: str
    data: Optional[Dict[str, Any]] = None
    error: Optional[str] = None
