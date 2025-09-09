from typing import List

from fastapi import APIRouter, Depends

from src.application.dto.aggregate_request import AggregateRequest
from src.application.dto.aggregate_response import AggregateResponse, ItemResult
from src.interface.controllers.telegram_controller import (
    TelegramController,
    get_telegram_controller,
)
from src.application.dto.search_request import SearchRequest
from src.infrastructure.validator.client import ValidatorClient


router = APIRouter(prefix="/aggregate", tags=["aggregator"])


@router.post(
    "/", 
    response_model=AggregateResponse,
    summary="Aggregate Search Results",
    description="Search multiple inputs and aggregate results in batch",
    response_description="Aggregated search results for all inputs"
)
async def aggregate(
    payload: AggregateRequest,
    telegram: TelegramController = Depends(get_telegram_controller),
) -> AggregateResponse:
    """
    Aggregate search results for multiple inputs.
    
    This endpoint processes multiple inputs in batch, automatically detecting
    the type of each input (phone, username, etc.) using the Validator service,
    and then performing the appropriate search for each input type.
    
    Args:
        payload: Request containing array of inputs to search
        telegram: Telegram controller dependency for performing searches
        
    Returns:
        AggregateResponse: Aggregated results for all inputs including:
            - success: Overall success status
            - results: List of individual search results
            - errors: Any global errors
            - metadata: Additional metadata
            
    Note:
        - Inputs are automatically validated and typed using Validator service
        - Unsupported input types are marked as failed with appropriate error
        - Each input is processed independently
    """
    validator = ValidatorClient()
    results: List[ItemResult] = []

    for raw in payload.inputs:
        dtype = await validator.detect(raw)
        if dtype == "phone":
            response = await telegram.search_user(SearchRequest(phone=raw))
            success = getattr(response, "success", False)
            data = getattr(response, "data", None)
            errors = getattr(response, "errors", None) or getattr(response, "details", None)
            results.append(ItemResult(input=raw, type=dtype, success=success, data=data, errors=errors))
        elif dtype == "username":
            response = await telegram.search_user(SearchRequest(username=raw))
            success = getattr(response, "success", False)
            data = getattr(response, "data", None)
            errors = getattr(response, "errors", None) or getattr(response, "details", None)
            results.append(ItemResult(input=raw, type=dtype, success=success, data=data, errors=errors))
        else:
            results.append(ItemResult(input=raw, type=dtype, success=False, data=None, errors=["Unsupported input type"]))

    overall_success = any(r.success for r in results)
    return AggregateResponse(success=overall_success, results=results, errors=None, metadata=None)



