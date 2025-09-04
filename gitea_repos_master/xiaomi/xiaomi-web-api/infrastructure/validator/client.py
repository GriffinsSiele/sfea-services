import asyncio
import re
from typing import Literal, Dict, Any

import httpx

from core.settings import get_settings


DataType = Literal["phone", "email", "unknown"]


class ValidatorClient:
    phone_re = re.compile(r"^[+]?[0-9]{7,15}$")
    email_re = re.compile(r"^[^@\s]+@[^@\s]+\.[^@\s]+$")

    def __init__(self):
        self.settings = get_settings()

    async def detect(self, value: str) -> DataType:
        meta = await self.detect_with_meta(value)
        return meta.get("type", "unknown")  # type: ignore

    async def detect_with_meta(self, value: str) -> Dict[str, Any]:
        if self.settings.validator_enabled and self.settings.validator_base_url:
            url = self.settings.validator_base_url.rstrip("/") + "/detect"
            headers: Dict[str, str] = {}
            if self.settings.validator_api_key:
                headers["Authorization"] = f"Bearer {self.settings.validator_api_key}"
            timeout = self.settings.validator_timeout_seconds
            retries = self.settings.validator_max_retries
            # simple circuit breaker
            if not hasattr(self, "_failures"):
                self._failures = 0
            if not hasattr(self, "_open_until"):
                self._open_until = 0.0
            async with httpx.AsyncClient(timeout=timeout, proxies=self.settings.proxy_url or None) as client:
                for attempt in range(retries + 1):
                    try:
                        resp = await client.post(url, headers=headers, json={"value": value})
                        resp.raise_for_status()
                        data = resp.json()
                        self._failures = 0
                        return {"type": data.get("type", "unknown"), "confidence": data.get("confidence", 0.0), "source": "validator"}
                    except Exception:
                        self._failures = min(getattr(self, "_failures", 0) + 1, 10)
                        if self._failures >= 5:
                            import time
                            self._open_until = time.time() + 30
                            break
                        if attempt >= retries:
                            break
                        await asyncio.sleep(0.5 * (2 ** attempt))
        # fallback
        if self.phone_re.match(value):
            return {"type": "phone", "confidence": 0.6, "source": "fallback"}
        if self.email_re.match(value):
            return {"type": "email", "confidence": 0.5, "source": "fallback"}
        return {"type": "unknown", "confidence": 0.2, "source": "fallback"}


