from typing import Dict, Any

from fastapi import APIRouter, Depends

from api.v1.schemas.service_schemas import ParseRequest, ParseResponse
from controllers.unified_controller import UnifiedController, get_unified_controller


router = APIRouter(prefix="/api/v1/xiaomi/phone", tags=["xiaomi-phone"])


@router.post("/parse", response_model=ParseResponse)
async def parse_phone(
    payload: ParseRequest,
    controller: UnifiedController = Depends(get_unified_controller),
) -> Dict[str, Any]:
    return await controller.parse_phone(payload)



