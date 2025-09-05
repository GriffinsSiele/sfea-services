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


@api_router.post("/parse", response_model=AggregatedResponse)
async def parse_batch(payload: ParseBatchRequest) -> AggregatedResponse:
    app_service = AggregatorApplicationService()
    return await app_service.parse_batch(payload.values)


@api_router.post("/xiaomi/parse", response_model=ParseResponse)
async def parse_xiaomi(payload: ParseItemRequest) -> ParseResponse:
    app_service = AggregatorApplicationService()
    return await app_service.parse_single(payload.value)



