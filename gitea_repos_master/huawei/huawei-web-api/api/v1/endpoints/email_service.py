from typing import Dict, Any

from fastapi import APIRouter, Depends

from api.v1.schemas.service_schemas import ParseRequest, ParseResponse
from controllers.unified_controller import UnifiedController, get_unified_controller


router = APIRouter(prefix="/api/v1/huawei/email", tags=["huawei-email"])


@router.post("/parse", response_model=ParseResponse)
async def parse_email(
    payload: ParseRequest,
    controller: UnifiedController = Depends(get_unified_controller),
) -> Dict[str, Any]:
    return await controller.parse_email(payload)


