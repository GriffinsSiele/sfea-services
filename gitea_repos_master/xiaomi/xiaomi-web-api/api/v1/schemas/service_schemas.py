from typing import Optional, List, Dict, Any
from pydantic import BaseModel, Field


class ParseRequest(BaseModel):
    value: str = Field(..., description="Single input value (phone or email)")


class ParseItem(BaseModel):
    input: str
    type: str
    normalized: Optional[str] = None
    data: Optional[Dict[str, Any]] = None
    result: str
    result_code: str
    notes: Optional[List[str]] = None


class ParseResponse(BaseModel):
    item: ParseItem


class AggregateRequest(BaseModel):
    inputs: List[str]


class AggregatedResponse(BaseModel):
    total: int
    results: List[ParseItem]



