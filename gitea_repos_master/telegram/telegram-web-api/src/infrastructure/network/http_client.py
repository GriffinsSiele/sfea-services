import asyncio
from typing import Any, Dict, Optional

import aiohttp


async def fetch_json(url: str, proxy_url: Optional[str] = None, timeout_seconds: int = 10) -> Dict[str, Any]:
    timeout = aiohttp.ClientTimeout(total=timeout_seconds)
    async with aiohttp.ClientSession(timeout=timeout) as session:
        async with session.get(url, proxy=proxy_url) as resp:
            resp.raise_for_status()
            return await resp.json(content_type=None)



