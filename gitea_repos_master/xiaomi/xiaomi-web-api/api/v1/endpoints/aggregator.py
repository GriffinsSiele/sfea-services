from typing import Dict, Any, List

from fastapi import APIRouter, Depends

from api.v1.schemas.service_schemas import AggregateRequest, AggregatedResponse, ParseItem, ParseRequest
from controllers.unified_controller import UnifiedController, get_unified_controller
from infrastructure.validator.client import ValidatorClient


router = APIRouter(prefix="/api/v1", tags=["xiaomi-aggregate"])


@router.post("/xiaomi/parse", response_model=AggregatedResponse)
async def aggregate_parse(
    payload: AggregateRequest,
    controller: UnifiedController = Depends(get_unified_controller),
) -> Dict[str, Any]:
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



