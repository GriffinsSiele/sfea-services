from typing import List

from fastapi import APIRouter

from app.api.v1.schemas import (
    AggregatedResponse,
    DataType,
    ParseBatchRequest,
    ParseItemRequest,
    ParseResponse,
)
from app.application.service import AggregatorApplicationService


api_router = APIRouter()


@api_router.post(
    "/parse", 
    response_model=AggregatedResponse,
    summary="Parse Batch Inputs",
    description="Parse multiple inputs in batch using Xiaomi service",
    response_description="Aggregated parsing results for all inputs"
)
async def parse_batch(payload: ParseBatchRequest) -> AggregatedResponse:
    """
    Parse multiple inputs in batch using the Xiaomi service.
    
    This endpoint processes multiple inputs simultaneously and returns
    aggregated results for all inputs, including success status and
    parsed data for each input.
    
    Args:
        payload: Batch request containing array of inputs to parse
        
    Returns:
        AggregatedResponse: Aggregated results including:
            - success: Overall success status
            - results: List of individual parsing results
            - errors: Any global errors
            - metadata: Additional metadata
            
    Note:
        - Each input is processed independently
        - Failed inputs don't affect other inputs in the batch
        - Results maintain the same order as input values
    """
    app_service = AggregatorApplicationService()
    return await app_service.parse_batch(payload.values)


@api_router.post(
    "/xiaomi/parse", 
    response_model=ParseResponse,
    summary="Parse Single Input",
    description="Parse a single input using Xiaomi service",
    response_description="Parsing result for the single input"
)
async def parse_xiaomi(payload: ParseItemRequest) -> ParseResponse:
    """
    Parse a single input using the Xiaomi service.
    
    This endpoint processes a single input and returns detailed
    parsing results including validation and data extraction.
    
    Args:
        payload: Single item request containing the input to parse
        
    Returns:
        ParseResponse: Parsing result including:
            - success: Whether parsing was successful
            - data: Parsed data from the input
            - errors: Any errors encountered during parsing
            - metadata: Additional parsing metadata
            
    Raises:
        HTTPException: For various error conditions
    """
    app_service = AggregatorApplicationService()
    return await app_service.parse_single(payload.value)



