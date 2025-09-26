import re
from typing import Dict, Any, List, Optional
from infrastructure.services.huawei_client import HuaweiServiceClient


class PhoneParseService:
    phone_re = re.compile(r"^[+]?[0-9]{7,15}$")

    def __init__(self):
        self.huawei_client = HuaweiServiceClient()

    def normalize(self, phone: str) -> str:
        phone = phone.strip()
        if phone.startswith("+"):
            return phone
        return "+" + phone

    async def parse(self, normalized_phone: str) -> Dict[str, Any] | None:
        """Parse phone number using Huawei API with enhanced data extraction"""
        try:
            # Call Huawei API with enhanced parsing
            api_response = await self.huawei_client.parse_phone(normalized_phone)
            
            if not api_response.get("found"):
                return {
                    "list__phones": [normalized_phone],
                    "status": "not_found",
                    "error": api_response.get("error"),
                    "source": "huawei"
                }
            
            # Extract parsed data
            parsed_data = api_response.get("parsed_data", {})
            confidence = api_response.get("confidence", 0.0)
            
            # Build comprehensive response
            result = {
                "list__phones": [normalized_phone],
                "status": "found",
                "source": "huawei",
                "confidence": confidence,
                "user_count": parsed_data.get("user_count", 0),
                "extracted_data": {
                    "phones": parsed_data.get("phones", []),
                    "emails": parsed_data.get("emails", []),
                    "names": parsed_data.get("names", []),
                    "addresses": parsed_data.get("addresses", []),
                    "users": parsed_data.get("users", [])
                },
                "additional_info": parsed_data.get("additional_info", {}),
                "timestamp": api_response.get("timestamp")
            }
            
            return result
            
        except Exception as e:
            return {
                "list__phones": [normalized_phone],
                "status": "error",
                "error": str(e),
                "source": "huawei"
            }


