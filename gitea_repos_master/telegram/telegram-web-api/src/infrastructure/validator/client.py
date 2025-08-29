import re
import asyncio
from typing import Literal, Optional, Dict, Any

import httpx

from src.config.settings import get_settings


DataType = Literal["phone", "email", "username", "unknown"]


class ValidatorClient:
    """SMK-RK Validator IS client with HTTP integration and local fallback."""

    phone_re = re.compile(r"^[+]?\d{7,15}$")
    email_re = re.compile(r"^[^@\s]+@[^@\s]+\.[^@\s]+$")
    username_re = re.compile(r"^[A-Za-z0-9_]{3,64}$")

    def __init__(self):
        self.settings = get_settings()

    async def detect(self, value: str) -> DataType:
        detected = await self.detect_with_meta(value)
        return detected["type"]  # type: ignore[return-value]

    async def detect_with_meta(self, value: str) -> Dict[str, Any]:
        if self.settings.VALIDATOR_ENABLED and self.settings.VALIDATOR_BASE_URL:
            url = self.settings.VALIDATOR_BASE_URL.rstrip('/') + '/detect'
            headers: Dict[str, str] = {}
            if self.settings.VALIDATOR_API_KEY:
                headers["Authorization"] = f"Bearer {self.settings.VALIDATOR_API_KEY}"
            timeout = self.settings.VALIDATOR_TIMEOUT_SECONDS
            retries = self.settings.VALIDATOR_MAX_RETRIES
            async with httpx.AsyncClient(timeout=timeout) as client:
                for attempt in range(retries + 1):
                    try:
                        resp = await client.post(url, headers=headers, json={"value": value})
                        resp.raise_for_status()
                        data = resp.json()
                        t = data.get("type", "unknown")
                        conf = float(data.get("confidence", 0.0))
                        return {"type": t, "confidence": conf, "source": "validator"}
                    except Exception:
                        if attempt >= retries:
                            break
                        await asyncio.sleep(0.5 * (2 ** attempt))
        # fallback
        return self._local_detect(value)

    def _local_detect(self, value: str) -> Dict[str, Any]:
        if self.phone_re.match(value):
            return {"type": "phone", "confidence": 0.6, "source": "fallback"}
        if self.email_re.match(value):
            return {"type": "email", "confidence": 0.5, "source": "fallback"}
        if self.username_re.match(value):
            return {"type": "username", "confidence": 0.5, "source": "fallback"}
        return {"type": "unknown", "confidence": 0.1, "source": "fallback"}


