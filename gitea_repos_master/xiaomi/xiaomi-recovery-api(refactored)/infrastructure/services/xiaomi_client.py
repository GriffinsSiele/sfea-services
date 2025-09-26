import asyncio
import re
from typing import Any, Dict, Optional, List
from datetime import datetime

from core.settings import get_settings


class XiaomiServiceClient:
    """Xiaomi service client with integrated captcha solving and data parsing"""
    
    def __init__(self) -> None:
        self.settings = get_settings()
        self._xiaomi_instance = None
        self._search_manager = None

    async def _get_xiaomi_instance(self):
        """Lazy initialization of Xiaomi instance"""
        if self._xiaomi_instance is None:
            # Import here to avoid circular imports
            from src.logic.xiaomi.xiaomi import Xiaomi
            self._xiaomi_instance = Xiaomi()
        return self._xiaomi_instance

    async def _get_search_manager(self):
        """Lazy initialization of search manager"""
        if self._search_manager is None:
            from src.logic.xiaomi.search_manager import XiaomiSearchManager
            self._search_manager = XiaomiSearchManager()
        return self._search_manager

    async def parse_phone(self, phone: str) -> Dict[str, Any]:
        """Parse phone number using Xiaomi recovery API"""
        try:
            search_manager = await self._get_search_manager()
            
            # Prepare the search manager
            await search_manager._prepare()
            
            # Create search data
            from src.fastapi.schemas import XiaomiSearchData
            search_data = XiaomiSearchData(payload=phone)
            
            # Perform search
            raw_response = await search_manager._search(search_data)
            
            return self._parse_xiaomi_response(raw_response, "phone", phone)
            
        except Exception as e:
            return {
                "found": False,
                "data_type": "phone",
                "original_value": phone,
                "error": str(e),
                "parsed_data": None,
                "source": "xiaomi"
            }

    async def parse_email(self, email: str) -> Dict[str, Any]:
        """Parse email using Xiaomi recovery API"""
        try:
            search_manager = await self._get_search_manager()
            
            # Prepare the search manager
            await search_manager._prepare()
            
            # Create search data
            from src.fastapi.schemas import XiaomiSearchData
            search_data = XiaomiSearchData(payload=email)
            
            # Perform search
            raw_response = await search_manager._search(search_data)
            
            return self._parse_xiaomi_response(raw_response, "email", email)
            
        except Exception as e:
            return {
                "found": False,
                "data_type": "email",
                "original_value": email,
                "error": str(e),
                "parsed_data": None,
                "source": "xiaomi"
            }

    def _parse_xiaomi_response(self, raw_response: Dict[str, Any], data_type: str, original_value: str) -> Dict[str, Any]:
        """Parse Xiaomi API response and extract recovery data"""
        if not raw_response or raw_response.get("result_code") != "FOUND":
            return {
                "found": False,
                "data_type": data_type,
                "original_value": original_value,
                "error": raw_response.get("error") if raw_response else "No response",
                "parsed_data": None,
                "source": "xiaomi"
            }

        # Extract recovery information
        parsed_data = self._extract_recovery_data(raw_response, data_type)
        
        return {
            "found": True,
            "data_type": data_type,
            "original_value": original_value,
            "parsed_data": parsed_data,
            "confidence": self._calculate_confidence(parsed_data),
            "source": "xiaomi",
            "timestamp": datetime.now().isoformat()
        }

    def _extract_recovery_data(self, response: Dict[str, Any], data_type: str) -> Dict[str, Any]:
        """Extract recovery information from Xiaomi response"""
        if not response:
            return {}

        extracted_data = {
            "recovery_status": "found",
            "data_type": data_type,
            "device_info": self._extract_device_info(response),
            "account_info": self._extract_account_info(response),
            "recovery_options": self._extract_recovery_options(response),
            "security_info": self._extract_security_info(response),
            "contact_methods": self._extract_contact_methods(response),
            "additional_data": self._extract_additional_data(response)
        }

        return extracted_data

    def _extract_device_info(self, response: Dict[str, Any]) -> Dict[str, Any]:
        """Extract device information from response"""
        device_info = {}
        
        # Common device fields that might be in the response
        device_fields = [
            "device_model", "device_name", "device_type", "device_id",
            "imei", "serial_number", "manufacturer", "os_version",
            "android_version", "miui_version", "device_status"
        ]
        
        for field in device_fields:
            if field in response and response[field]:
                device_info[field] = str(response[field]).strip()
        
        # Extract from nested structures
        if "device" in response and isinstance(response["device"], dict):
            for key, value in response["device"].items():
                if value:
                    device_info[f"device_{key}"] = str(value).strip()
        
        return device_info

    def _extract_account_info(self, response: Dict[str, Any]) -> Dict[str, Any]:
        """Extract account information from response"""
        account_info = {}
        
        account_fields = [
            "account_id", "user_id", "username", "email", "phone",
            "account_status", "registration_date", "last_login",
            "account_type", "verification_status", "account_level"
        ]
        
        for field in account_fields:
            if field in response and response[field]:
                account_info[field] = str(response[field]).strip()
        
        # Extract from nested structures
        if "account" in response and isinstance(response["account"], dict):
            for key, value in response["account"].items():
                if value:
                    account_info[f"account_{key}"] = str(value).strip()
        
        return account_info

    def _extract_recovery_options(self, response: Dict[str, Any]) -> Dict[str, Any]:
        """Extract available recovery options"""
        recovery_options = {
            "available_methods": [],
            "recommended_method": None,
            "success_rate": None,
            "estimated_time": None
        }
        
        # Look for recovery method information
        recovery_fields = [
            "recovery_methods", "available_recovery", "recovery_options",
            "reset_methods", "unlock_methods"
        ]
        
        for field in recovery_fields:
            if field in response and response[field]:
                if isinstance(response[field], list):
                    recovery_options["available_methods"].extend(response[field])
                elif isinstance(response[field], str):
                    recovery_options["available_methods"].append(response[field])
        
        # Look for specific recovery information
        if "recommended_method" in response:
            recovery_options["recommended_method"] = str(response["recommended_method"])
        
        if "success_rate" in response:
            recovery_options["success_rate"] = str(response["success_rate"])
        
        if "estimated_time" in response:
            recovery_options["estimated_time"] = str(response["estimated_time"])
        
        return recovery_options

    def _extract_security_info(self, response: Dict[str, Any]) -> Dict[str, Any]:
        """Extract security-related information"""
        security_info = {}
        
        security_fields = [
            "security_level", "two_factor_enabled", "biometric_enabled",
            "pin_set", "pattern_set", "password_strength", "security_questions",
            "backup_codes", "trusted_devices"
        ]
        
        for field in security_fields:
            if field in response and response[field] is not None:
                security_info[field] = response[field]
        
        return security_info

    def _extract_contact_methods(self, response: Dict[str, Any]) -> Dict[str, Any]:
        """Extract available contact methods for recovery"""
        contact_methods = {
            "phones": [],
            "emails": [],
            "alternative_contacts": []
        }
        
        # Extract phone numbers
        phone_fields = ["phone", "mobile", "contact_phone", "recovery_phone"]
        for field in phone_fields:
            if field in response and response[field]:
                phone = str(response[field]).strip()
                if self._is_valid_phone(phone):
                    contact_methods["phones"].append(self._normalize_phone(phone))
        
        # Extract emails
        email_fields = ["email", "contact_email", "recovery_email"]
        for field in email_fields:
            if field in response and response[field]:
                email = str(response[field]).strip().lower()
                if self._is_valid_email(email):
                    contact_methods["emails"].append(email)
        
        # Extract alternative contacts
        if "alternative_contacts" in response and isinstance(response["alternative_contacts"], list):
            contact_methods["alternative_contacts"] = response["alternative_contacts"]
        
        return contact_methods

    def _extract_additional_data(self, response: Dict[str, Any]) -> Dict[str, Any]:
        """Extract additional data that doesn't fit other categories"""
        additional_data = {}
        
        # Extract any remaining meaningful data
        excluded_fields = {
            "result", "result_code", "status", "error", "message",
            "device", "account", "recovery_methods", "security",
            "phone", "email", "mobile", "contact_phone", "contact_email"
        }
        
        for key, value in response.items():
            if key not in excluded_fields and value is not None and str(value).strip():
                additional_data[key] = str(value).strip()
        
        return additional_data

    def _is_valid_phone(self, phone: str) -> bool:
        """Validate phone number format"""
        phone_digits = re.sub(r'\D', '', phone)
        return 7 <= len(phone_digits) <= 15

    def _is_valid_email(self, email: str) -> bool:
        """Validate email format"""
        email_pattern = r'^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$'
        return bool(re.match(email_pattern, email))

    def _normalize_phone(self, phone: str) -> str:
        """Normalize phone number format"""
        phone_digits = re.sub(r'\D', '', phone)
        if phone_digits.startswith('7') and len(phone_digits) == 11:
            return f"+{phone_digits}"
        elif phone_digits.startswith('8') and len(phone_digits) == 11:
            return f"+7{phone_digits[1:]}"
        elif len(phone_digits) >= 7:
            return f"+{phone_digits}"
        return phone

    def _calculate_confidence(self, parsed_data: Dict[str, Any]) -> float:
        """Calculate confidence score for parsed recovery data"""
        if not parsed_data:
            return 0.0

        score = 0.0
        max_score = 10.0

        # Base score for finding recovery data
        if parsed_data.get("recovery_status") == "found":
            score += 3.0

        # Score for device information completeness
        device_info = parsed_data.get("device_info", {})
        if device_info:
            score += 1.0
            device_fields = ["device_model", "device_id", "imei", "serial_number"]
            device_score = sum(1 for field in device_fields if device_info.get(field))
            score += min(device_score * 0.5, 2.0)  # Max 2 points

        # Score for account information
        account_info = parsed_data.get("account_info", {})
        if account_info:
            score += 1.0
            account_fields = ["account_id", "username", "email", "phone"]
            account_score = sum(1 for field in account_fields if account_info.get(field))
            score += min(account_score * 0.5, 1.5)  # Max 1.5 points

        # Score for recovery options
        recovery_options = parsed_data.get("recovery_options", {})
        if recovery_options.get("available_methods"):
            score += 1.0
            methods_count = len(recovery_options["available_methods"])
            score += min(methods_count * 0.3, 1.0)  # Max 1 point

        # Score for contact methods
        contact_methods = parsed_data.get("contact_methods", {})
        phones_count = len(contact_methods.get("phones", []))
        emails_count = len(contact_methods.get("emails", []))
        score += min(phones_count * 0.5, 1.0)  # Max 1 point
        score += min(emails_count * 0.5, 0.5)  # Max 0.5 points

        return min(score / max_score, 1.0)
