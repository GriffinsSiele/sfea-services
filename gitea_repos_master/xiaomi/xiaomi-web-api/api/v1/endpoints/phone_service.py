from typing import Dict, Any

from fastapi import APIRouter, Depends

from api.v1.schemas.service_schemas import ParseRequest, ParseResponse
from controllers.unified_controller import UnifiedController, get_unified_controller


router = APIRouter(prefix="/xiaomi/phone", tags=["xiaomi-phone"])


@router.post(
    "/parse", 
    response_model=ParseResponse,
    summary="Parse Phone Number",
    description="Parse a phone number using Xiaomi service",
    response_description="Parsed phone number results"
)
async def parse_phone(
    payload: ParseRequest,
    controller: UnifiedController = Depends(get_unified_controller),
) -> Dict[str, Any]:
    """
    Parse a phone number using the Xiaomi service.
    
    This endpoint processes phone numbers and returns standardized
    parsing results including validation and formatting information.
    
    Args:
        payload: Parse request containing the phone number to process
        controller: Unified controller dependency for processing
        
    Returns:
        Dict[str, Any]: Parsed phone number results including:
            - success: Whether parsing was successful
            - data: Parsed phone number data
            - errors: Any errors encountered during parsing
            
    Raises:
        HTTPException: For various error conditions
    """
    return await controller.parse_phone(payload)



