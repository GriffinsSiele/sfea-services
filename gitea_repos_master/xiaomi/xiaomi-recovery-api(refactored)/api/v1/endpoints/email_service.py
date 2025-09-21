from typing import Dict, Any

from fastapi import APIRouter, Depends

from api.v1.schemas.service_schemas import ParseRequest, ParseResponse
from controllers.unified_controller import UnifiedController, get_unified_controller


router = APIRouter(prefix="/xiaomi/email", tags=["xiaomi-email"])


@router.post(
    "/parse", 
    response_model=ParseResponse,
    summary="Parse Email Address",
    description="Parse an email address using Xiaomi service",
    response_description="Parsed email address results"
)
async def parse_email(
    payload: ParseRequest,
    controller: UnifiedController = Depends(get_unified_controller),
) -> Dict[str, Any]:
    """
    Parse an email address using the Xiaomi service.
    
    This endpoint processes email addresses and returns standardized
    parsing results including validation and formatting information.
    
    Args:
        payload: Parse request containing the email address to process
        controller: Unified controller dependency for processing
        
    Returns:
        Dict[str, Any]: Parsed email address results including:
            - success: Whether parsing was successful
            - data: Parsed email address data
            - errors: Any errors encountered during parsing
            
    Raises:
        HTTPException: For various error conditions
    """
    return await controller.parse_email(payload)



