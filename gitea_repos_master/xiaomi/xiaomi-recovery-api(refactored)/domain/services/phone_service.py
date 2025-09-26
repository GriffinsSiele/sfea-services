import re
from typing import Dict, List, Optional, Any
from infrastructure.services.xiaomi_client import XiaomiServiceClient


class PhoneParseService:
    def __init__(self):
        self.xiaomi_client = XiaomiServiceClient()

    def normalize(self, phone: str) -> str:
        phone = phone.strip()
        if phone.startswith("+"):
            return phone
        return "+" + phone

    async def parse(self, normalized_phone: str) -> Dict[str, Any] | None:
        """Parse phone number using Xiaomi recovery API with enhanced data extraction"""
        try:
            # Call Xiaomi API with enhanced parsing
            api_response = await self.xiaomi_client.parse_phone(normalized_phone)
            
            if not api_response.get("found"):
                return {
                    "list__phones": [normalized_phone],
                    "status": "not_found",
                    "error": api_response.get("error"),
                    "source": "xiaomi"
                }
            
            # Extract parsed data
            parsed_data = api_response.get("parsed_data", {})
            confidence = api_response.get("confidence", 0.0)
            
            # Build comprehensive response
            result = {
                "list__phones": [normalized_phone],
                "status": "found",
                "source": "xiaomi",
                "confidence": confidence,
                "recovery_status": parsed_data.get("recovery_status", "unknown"),
                "extracted_data": {
                    "device_info": parsed_data.get("device_info", {}),
                    "account_info": parsed_data.get("account_info", {}),
                    "recovery_options": parsed_data.get("recovery_options", {}),
                    "security_info": parsed_data.get("security_info", {}),
                    "contact_methods": parsed_data.get("contact_methods", {}),
                    "additional_data": parsed_data.get("additional_data", {})
                },
                "timestamp": api_response.get("timestamp")
            }
            
            return result
            
        except Exception as e:
            return {
                "list__phones": [normalized_phone],
                "status": "error",
                "error": str(e),
                "source": "xiaomi"
            }



