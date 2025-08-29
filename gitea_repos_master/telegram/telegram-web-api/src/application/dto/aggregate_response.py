from typing import Any, Dict, List, Optional
from pydantic import BaseModel


class ItemResult(BaseModel):
    input: str
    type: str
    success: bool
    data: Optional[Any] = None
    errors: Optional[List[str]] = None


class AggregateResponse(BaseModel):
    success: bool
    results: List[ItemResult]
    errors: Optional[List[str]] = None
    metadata: Optional[Dict[str, Any]] = None



