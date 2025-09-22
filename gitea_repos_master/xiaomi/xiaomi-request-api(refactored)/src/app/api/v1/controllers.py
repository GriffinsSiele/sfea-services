from typing import List

from fastapi import APIRouter

from app.api.v1.schemas import (
    AggregatedResponse,
    DataType,
    ParseBatchRequest,
    ParseItemRequest,
    ParseResponse,
)
from app.application import service as application_service_module


api_router = APIRouter()


@api_router.post(
    "/parse", 
    response_model=AggregatedResponse,
    summary="Parse Batch Inputs",
    description="Parse multiple inputs in batch using Xiaomi service",
    response_description="Aggregated parsing results for all inputs"
)
async def parse_batch(payload: ParseBatchRequest) -> AggregatedResponse:
	"""Parse multiple inputs in batch using the Xiaomi service."""
	app_service = application_service_module.AggregatorApplicationService()
	return await app_service.parse_batch(payload.values)


@api_router.post(
    "/xiaomi/parse", 
    response_model=ParseResponse,
    summary="Parse Single Input",
    description="Parse a single input using Xiaomi service",
    response_description="Parsing result for the single input"
)
async def parse_xiaomi(payload: ParseItemRequest) -> ParseResponse:
	"""Parse a single input using the Xiaomi service."""
	app_service = application_service_module.AggregatorApplicationService()
	return await app_service.parse_single(payload.value)



