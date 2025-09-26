from typing import Any, Dict, List

from fastapi import APIRouter

from app.infrastructure.validator.client import ValidatorClient
from app.infrastructure.services.xiaomi_client import XiaomiServiceClient

router = APIRouter()


@router.post("/aggregate")
async def aggregate(payload: Dict[str, List[str]]) -> List[Dict[str, Any]]:
	values = payload.get("query") or payload.get("values") or []
	validator = ValidatorClient()
	xiaomi = XiaomiServiceClient()

	results: List[Dict[str, Any]] = []
	validator_items = await validator.validate_batch(values)
	results.extend(validator_items)

	for v in values:
		meta = await validator.detect_with_meta(v)
		dt = meta.get("type", "unknown")
		if dt in ("phone", "email"):
			resp = await xiaomi.parse_value(value=v, dtype=dt)  # type: ignore
			body: Dict[str, Any] = {"request_data": v, "type": dt, "clean_data": v}
			extra: Dict[str, Any] = {"records": (resp.data or {}).get("records", []) if isinstance(resp.data, dict) else resp.data}
			results.append({
				"headers": {"sender": "xiaomi"},
				"body": body,
				"extra": extra,
			})
		else:
			results.append({
				"headers": {"sender": "xiaomi"},
				"body": {"request_data": v, "type": "unknown", "clean_data": v},
				"extra": {"notes": ["Unsupported data type"]},
			})

	return results


