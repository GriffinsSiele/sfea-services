from dataclasses import dataclass
from typing import Any, Dict, Literal, Optional


# Domain primitives kept intentionally thin for orchestration-only service
DataType = Literal["phone", "email", "unknown"]


@dataclass
class ParsedResult:
    input: str
    type: DataType
    service: str = "xiaomi"
    success: bool = False
    found: Optional[bool] = None
    data: Optional[Dict[str, Any]] = None
    error: Optional[str] = None
    error_code: Optional[str] = None


