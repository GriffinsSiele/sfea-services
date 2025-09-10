import httpx

from app.api.v1.schemas import ParseResponse
from app.core.settings import get_settings
from app.domain.models import DataType, ParsedResult


class XiaomiServiceClient:
    def __init__(self) -> None:
        self.settings = get_settings()

    async def parse_value(self, value: str, dtype: DataType) -> ParseResponse:
        base = self.settings.xiaomi_request_api_base_url.rstrip("/")
        url = base + "/search"
        payload = {"email": value} if dtype == "email" else {"phone": value.lstrip("+")}
        try:
            timeout = self.settings.request_timeout_seconds
            async with httpx.AsyncClient(timeout=timeout, proxies=self.settings.proxy_url or None) as client:
                resp = await client.post(url, json=payload)
                if resp.status_code == 204:
                    return ParseResponse(
                        input=value,
                        type=dtype,
                        service="xiaomi",
                        success=True,
                        found=False,
                        data=None,
                    )
                data = resp.json()
                status = data.get("status")
                code = data.get("code")
                if status == "ok" and code == 200:
                    return ParseResponse(
                        input=value,
                        type=dtype,
                        service="xiaomi",
                        success=True,
                        found=True,
                        data={"records": data.get("records", [])},
                    )
                return ParseResponse(
                    input=value,
                    type=dtype,
                    service="xiaomi",
                    success=False,
                    found=None,
                    error=data.get("message", "Unknown error"),
                    error_code=f"XIAOMI_{code}",
                )
        except Exception as e:  # pragma: no cover
            return ParseResponse(
                input=value,
                type=dtype,
                service="xiaomi",
                success=False,
                found=None,
                error=str(e),
                error_code="XIAOMI_UPSTREAM_ERROR",
            )



