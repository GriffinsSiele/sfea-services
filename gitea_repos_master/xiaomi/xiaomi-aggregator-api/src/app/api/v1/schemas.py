from typing import Any, Dict, List, Literal, Optional

from pydantic import BaseModel, Field, model_validator


DataType = Literal["phone", "email", "unknown"]


class ParseItemRequest(BaseModel):
    value: str = Field(..., description="Input value to parse (phone/email/etc)")


class ParseBatchRequest(BaseModel):
    values: List[str] = Field(..., description="List of values for parsing")


class ParseResponse(BaseModel):
    input: str
    type: DataType
    service: str
    success: bool
    found: Optional[bool] = None
    data: Optional[Dict[str, Any]] = None
    error: Optional[str] = None
    error_code: Optional[str] = None


class AggregatedResponse(BaseModel):
    success: bool
    total: int
    items: List[ParseResponse]

    @model_validator(mode="after")
    def check_total(self) -> "AggregatedResponse":
        if self.total != len(self.items):
            self.total = len(self.items)
        return self



