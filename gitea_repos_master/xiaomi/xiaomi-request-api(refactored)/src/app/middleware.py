import json
import uuid
from typing import Callable

from fastapi import FastAPI, Request
from starlette.middleware.base import BaseHTTPMiddleware
from starlette.responses import Response

from app.api.v1.schemas import AggregatedResponse, ParseResponse
from app.core.settings import get_settings


class RequestIDMiddleware(BaseHTTPMiddleware):
    async def dispatch(self, request: Request, call_next: Callable):
        req_id = request.headers.get("X-Request-ID", str(uuid.uuid4()))
        request.state.request_id = req_id
        response = await call_next(request)
        response.headers["X-Request-ID"] = req_id
        return response


class ResponseSchemaValidationMiddleware(BaseHTTPMiddleware):
    async def dispatch(self, request: Request, call_next: Callable):
        response = await call_next(request)
        # Only validate successful JSON responses
        if response.status_code >= 400:
            return response
        content_type = response.headers.get("Content-Type", "")
        if not content_type.startswith("application/json"):
            return response

        body_chunks = [chunk async for chunk in response.body_iterator]
        body = b"".join(body_chunks)
        try:
            text = body.decode("utf-8")
            if request.url.path.endswith("/api/v1/parse"):
                AggregatedResponse.model_validate_json(text)
            elif request.url.path.endswith("/api/v1/xiaomi/parse"):
                ParseResponse.model_validate_json(text)
        except Exception:
            body = json.dumps(
                {
                    "success": False,
                    "error": "Response schema validation failed",
                    "error_code": "SCHEMA_INVALID",
                }
            ).encode("utf-8")
            response.status_code = 500
            response.headers["Content-Type"] = "application/json"
        return Response(
            content=body, status_code=response.status_code, headers=dict(response.headers)
        )


def add_middlewares(app: FastAPI) -> None:
    app.add_middleware(RequestIDMiddleware)
    app.add_middleware(ResponseSchemaValidationMiddleware)

    settings = get_settings()
    if settings.redis_url:
        try:
            import aioredis  # type: ignore
        except Exception:
            aioredis = None  # type: ignore
        if aioredis:
            class RedisRateLimitMiddleware(BaseHTTPMiddleware):
                async def dispatch(self, request: Request, call_next: Callable):
                    client_id = request.headers.get("X-Client-ID") or (
                        request.client.host if request.client else "unknown"
                    )
                    window = settings.rate_limit_window_seconds
                    max_req = settings.rate_limit_max_requests
                    redis = await aioredis.from_url(settings.redis_url)
                    key = f"ratelimit:{client_id}:{window}"
                    current = await redis.incr(key)
                    if current == 1:
                        await redis.expire(key, window)
                    if current > max_req:
                        return Response(
                            content=b'{"success": false, "error": "Rate limit exceeded", "error_code": "RATE_LIMIT"}',
                            status_code=429,
                            headers={"Content-Type": "application/json"},
                        )
                    return await call_next(request)

            app.add_middleware(RedisRateLimitMiddleware)



