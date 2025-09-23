import httpx
from typing import Any, Dict, Optional

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
		return await self._post({"phone": phone.lstrip("+")})

	async def parse_email(self, email: str) -> Dict[str, Any]:
		return await self._post({"email": email})
