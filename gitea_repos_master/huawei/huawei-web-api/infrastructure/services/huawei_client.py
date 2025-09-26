import httpx
import re
from typing import Any, Dict, Optional, List
from datetime import datetime

from core.settings import get_settings


class HuaweiServiceClient:
	def __init__(self) -> None:
		self.settings = get_settings()

	async def _post(self, payload: Dict[str, Any]) -> Dict[str, Any]:
		base = (getattr(self.settings, "huawei_base_url", None) or "").rstrip("/")
		url = base + "/search"
		timeout = getattr(self.settings, "request_timeout_seconds", 15)
		proxies = self.settings.proxy_url or None
		async with httpx.AsyncClient(timeout=timeout, proxies=proxies) as client:
			resp = await client.post(url, json=payload)
			if resp.status_code == 204:
				return {"found": False, "records": []}
			data = resp.json()
			if isinstance(data, dict) and data.get("status") == "ok":
				return {"found": True, "records": data.get("records", [])}
			return {"found": False, "error": data.get("message", "Unknown error"), "code": data.get("code")}

	async def parse_phone(self, phone: str) -> Dict[str, Any]:
		raw_response = await self._post({"phone": phone.lstrip("+")})
		return self._parse_huawei_response(raw_response, "phone", phone)

	async def parse_email(self, email: str) -> Dict[str, Any]:
		raw_response = await self._post({"email": email})
		return self._parse_huawei_response(raw_response, "email", email)

	def _parse_huawei_response(self, raw_response: Dict[str, Any], data_type: str, original_value: str) -> Dict[str, Any]:
		"""Parse Huawei API response and extract meaningful user data"""
		if not raw_response.get("found"):
			return {
				"found": False,
				"data_type": data_type,
				"original_value": original_value,
				"error": raw_response.get("error"),
				"code": raw_response.get("code"),
				"parsed_data": None
			}

		records = raw_response.get("records", [])
		parsed_data = self._extract_user_data(records, data_type)
		
		return {
			"found": True,
			"data_type": data_type,
			"original_value": original_value,
			"parsed_data": parsed_data,
			"confidence": self._calculate_confidence(parsed_data),
			"source": "huawei",
			"timestamp": datetime.now().isoformat()
		}

	def _extract_user_data(self, records: List[Dict], data_type: str) -> Dict[str, Any]:
		"""Extract and structure user data from Huawei API records"""
		if not records:
			return {}

		# Process all records and extract meaningful information
		extracted_data = {
			"user_count": len(records),
			"users": [],
			"phones": set(),
			"emails": set(),
			"names": set(),
			"addresses": set(),
			"additional_info": {}
		}

		for record in records:
			user_data = self._parse_single_record(record)
			if user_data:
				extracted_data["users"].append(user_data)
				
				# Collect all phones, emails, names, addresses
				if user_data.get("phone"):
					extracted_data["phones"].add(user_data["phone"])
				if user_data.get("email"):
					extracted_data["emails"].add(user_data["email"])
				if user_data.get("name"):
					extracted_data["names"].add(user_data["name"])
				if user_data.get("address"):
					extracted_data["addresses"].add(user_data["address"])

		# Convert sets to lists for JSON serialization
		extracted_data["phones"] = list(extracted_data["phones"])
		extracted_data["emails"] = list(extracted_data["emails"])
		extracted_data["names"] = list(extracted_data["names"])
		extracted_data["addresses"] = list(extracted_data["addresses"])

		return extracted_data

	def _parse_single_record(self, record: Dict[str, Any]) -> Optional[Dict[str, Any]]:
		"""Parse a single record from Huawei API response"""
		if not isinstance(record, dict):
			return None

		parsed_record = {
			"phone": self._extract_phone(record),
			"email": self._extract_email(record),
			"name": self._extract_name(record),
			"address": self._extract_address(record),
			"device_info": self._extract_device_info(record),
			"account_info": self._extract_account_info(record),
			"raw_data": record  # Keep original for debugging
		}

		# Only return if we found at least some meaningful data
		meaningful_fields = [k for k, v in parsed_record.items() 
							if k != "raw_data" and v is not None and v != ""]
		return parsed_record if meaningful_fields else None

	def _extract_phone(self, record: Dict[str, Any]) -> Optional[str]:
		"""Extract phone number from record"""
		phone_fields = ["phone", "mobile", "phone_number", "contact_phone"]
		for field in phone_fields:
			if field in record and record[field]:
				phone = str(record[field]).strip()
				if self._is_valid_phone(phone):
					return self._normalize_phone(phone)
		return None

	def _extract_email(self, record: Dict[str, Any]) -> Optional[str]:
		"""Extract email from record"""
		email_fields = ["email", "email_address", "contact_email", "mail"]
		for field in email_fields:
			if field in record and record[field]:
				email = str(record[field]).strip().lower()
				if self._is_valid_email(email):
					return email
		return None

	def _extract_name(self, record: Dict[str, Any]) -> Optional[str]:
		"""Extract name from record"""
		name_fields = ["name", "full_name", "username", "display_name", "nickname"]
		for field in name_fields:
			if field in record and record[field]:
				name = str(record[field]).strip()
				if len(name) > 1:  # Basic validation
					return name
		return None

	def _extract_address(self, record: Dict[str, Any]) -> Optional[str]:
		"""Extract address from record"""
		address_fields = ["address", "location", "city", "country", "region"]
		address_parts = []
		for field in address_fields:
			if field in record and record[field]:
				address_parts.append(str(record[field]).strip())
		return ", ".join(address_parts) if address_parts else None

	def _extract_device_info(self, record: Dict[str, Any]) -> Optional[Dict[str, Any]]:
		"""Extract device information from record"""
		device_fields = ["device_model", "device_type", "os_version", "app_version"]
		device_info = {}
		for field in device_fields:
			if field in record and record[field]:
				device_info[field] = str(record[field]).strip()
		return device_info if device_info else None

	def _extract_account_info(self, record: Dict[str, Any]) -> Optional[Dict[str, Any]]:
		"""Extract account information from record"""
		account_fields = ["account_id", "user_id", "registration_date", "last_login", "status"]
		account_info = {}
		for field in account_fields:
			if field in record and record[field]:
				account_info[field] = str(record[field]).strip()
		return account_info if account_info else None

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
		"""Calculate confidence score for parsed data"""
		if not parsed_data:
			return 0.0

		score = 0.0
		max_score = 10.0

		# Base score for finding any data
		if parsed_data.get("user_count", 0) > 0:
			score += 2.0

		# Score for data completeness
		users = parsed_data.get("users", [])
		if users:
			score += 1.0  # Found users
			
			# Score for data quality in first user
			first_user = users[0]
			quality_fields = ["phone", "email", "name", "address"]
			quality_score = sum(1 for field in quality_fields if first_user.get(field))
			score += min(quality_score * 0.5, 3.0)  # Max 3 points for quality

		# Score for multiple data points
		phones_count = len(parsed_data.get("phones", []))
		emails_count = len(parsed_data.get("emails", []))
		names_count = len(parsed_data.get("names", []))
		
		score += min(phones_count * 0.5, 2.0)  # Max 2 points for phones
		score += min(emails_count * 0.5, 1.0)  # Max 1 point for emails
		score += min(names_count * 0.5, 1.0)   # Max 1 point for names

		return min(score / max_score, 1.0)


