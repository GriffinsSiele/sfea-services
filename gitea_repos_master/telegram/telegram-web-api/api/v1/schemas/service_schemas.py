"""Compatibility schemas that re-export existing DTOs from the DDD layer."""

from src.application.dto.search_request import SearchRequest
from src.application.dto.search_response import SearchResponse
from src.application.dto.error_response import ErrorResponse

__all__ = [
    "SearchRequest",
    "SearchResponse",
    "ErrorResponse",
]



