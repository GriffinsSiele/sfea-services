import re
from typing import Dict, Any, List, Optional
from infrastructure.services.huawei_client import HuaweiServiceClient


class EmailParseService:
    email_re = re.compile(r"^[^@\s]+@[^@\s]+\.[^@\s]+$")

    def __init__(self):
        self.huawei_client = HuaweiServiceClient()

    def normalize(self, email: str) -> str:
        return email.strip().lower()

    async def parse(self, normalized_email: str) -> Dict[str, Any] | None:
        """Parse email using Huawei API with enhanced data extraction"""
        try:
            # Call Huawei API with enhanced parsing
            api_response = await self.huawei_client.parse_email(normalized_email)
            
            if not api_response.get("found"):
                return {
                    "list__emails": [normalized_email],
                    "status": "not_found",
                    "error": api_response.get("error"),
                    "source": "huawei"
                }
            
            # Extract parsed data
            parsed_data = api_response.get("parsed_data", {})
            confidence = api_response.get("confidence", 0.0)
            
            # Build comprehensive response
            result = {
                "list__emails": [normalized_email],
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
                "list__emails": [normalized_email],
                "status": "error",
                "error": str(e),
                "source": "huawei"
            }


