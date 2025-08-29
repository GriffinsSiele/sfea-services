from typing import Dict, Any
from src.application.dto.search_request import SearchRequest


def validate_search_request(request: SearchRequest) -> Dict[str, Any]:
    """Validate search request parameters"""
    
    # Check if exactly one of phone/username/value is provided
    provided = [x for x in [request.phone, request.username, request.value] if x]
    # Preserve legacy messages for test expectations
    if request.phone and request.username and not request.value:
        return {
            "valid": False,
            "error": "Cannot provide both phone and username",
            "details": {
                "phone": request.phone,
                "username": request.username,
                "rule": "Only one search parameter allowed"
            }
        }
    if len(provided) > 1:
        return {
            "valid": False,
            "error": "Provide only one of phone, username, or value",
            "details": {
                "phone": request.phone,
                "username": request.username,
                "value": request.value,
                "rule": "Only one search parameter allowed"
            }
        }
    
    if len(provided) == 0:
        return {
            "valid": False,
            "error": "Must provide either phone or username",
            "details": {
                "rule": "At least one search parameter required"
            }
        }
    
    # Validate phone number format if provided
    if request.phone:
        try:
            from src.domain.value_objects.phone_number import PhoneNumber
            PhoneNumber(request.phone)
        except ValueError as e:
            return {
                "valid": False,
                "error": f"Invalid phone number format: {str(e)}",
                "details": {
                    "phone": request.phone,
                    "expected_format": "7XXXXXXXXXX or similar"
                }
            }
    
    # Validate username format if provided
    if request.username:
        try:
            from src.domain.value_objects.username import Username
            Username(request.username)
        except ValueError as e:
            return {
                "valid": False,
                "error": f"Invalid username format: {str(e)}",
                "details": {
                    "username": request.username,
                    "expected_format": "5-32 characters, letters/numbers/underscores, starts with letter"
                }
            }
    # If unified value provided, accept it here and let controller/validator resolve
    return {"valid": True}
