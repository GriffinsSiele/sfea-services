from typing import List

from app.api.v1.schemas import AggregatedResponse, ParseResponse
from app.domain.models import DataType, ParsedResult
from app.infrastructure.services import xiaomi_client as xiaomi_client_module
from app.infrastructure.validator import client as validator_client_module


class AggregatorApplicationService:
    def __init__(self) -> None:
        # Import via modules so external tests can patch using the original paths
        self.validator = validator_client_module.ValidatorClient()
        self.xiaomi_client = xiaomi_client_module.XiaomiServiceClient()

    async def parse_single(self, value: str) -> ParseResponse:
        meta = await self.validator.detect_with_meta(value)
        dtype: DataType = meta.get("type", "unknown")  # type: ignore
        if dtype not in ("phone", "email"):
            return ParseResponse(
                input=value,
                type="unknown",
                service="none",
                success=False,
                found=None,
                error="Unsupported data type",
                error_code="TYPE_UNSUPPORTED",
            )
        return await self.xiaomi_client.parse_value(value=value, dtype=dtype)

    async def parse_batch(self, values: List[str]) -> AggregatedResponse:
        results: List[ParseResponse] = []
        for value in values:
            res = await self.parse_single(value)
            results.append(res)
        return AggregatedResponse(success=True, total=len(results), items=results)


