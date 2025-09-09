from typing import Dict, Any, List

from fastapi import APIRouter, Depends

from api.v1.schemas.service_schemas import AggregateRequest, AggregatedResponse, ParseItem, ParseRequest
from controllers.unified_controller import UnifiedController, get_unified_controller
from infrastructure.validator.client import ValidatorClient


router = APIRouter(prefix="/huawei", tags=["huawei-aggregate"])


@router.post(
    "/parse", 
    response_model=AggregatedResponse,
    summary="Aggregate Parse Inputs",
    description="Parse multiple inputs using Huawei service with automatic type detection",
    response_description="Aggregated parsing results for all inputs"
)
async def aggregate_parse(
    payload: AggregateRequest,
    controller: UnifiedController = Depends(get_unified_controller),
) -> Dict[str, Any]:
    """
    Aggregate parse multiple inputs using Huawei service.
    
    This endpoint processes multiple inputs in batch, automatically detecting
    the type of each input (phone, email, etc.) using the Validator service,
    and then performing the appropriate parsing for each input type.
    
    Args:
        payload: Request containing array of inputs to parse
        controller: Unified controller dependency for processing
        
    Returns:
        Dict[str, Any]: Aggregated results for all inputs including:
            - total: Total number of inputs processed
            - results: List of individual parsing results
            - Each result contains input, type, normalized data, and result status
            
    Note:
        - Inputs are automatically validated and typed using Validator service
        - Unsupported input types are marked as NOT_FOUND with appropriate notes
        - Each input is processed independently
    """
    validator = ValidatorClient()
    results: List[ParseItem] = []
    for raw in payload.inputs:
        dtype = await validator.detect(raw)
        if dtype == "phone":
            resp = await controller.parse_phone(ParseRequest(value=raw))
            item = ParseItem(**resp["item"])  # normalized controller output
        elif dtype == "email":
            resp = await controller.parse_email(ParseRequest(value=raw))
            item = ParseItem(**resp["item"])  # normalized controller output
        else:
            item = ParseItem(
                input=raw,
                type="unknown",
                normalized=None,
                data=None,
                result="Не найден",
                result_code="NOT_FOUND",
                notes=["Unsupported input type"],
            )
        results.append(item)

    return AggregatedResponse(total=len(results), results=results).model_dump()


