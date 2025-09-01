import json
import uuid
from typing import Callable

from fastapi import FastAPI, Request
from starlette.middleware.base import BaseHTTPMiddleware
from starlette.responses import Response

from api.v1.schemas.service_schemas import AggregatedResponse, ParseResponse


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
        content_type = response.headers.get("Content-Type", "")
        if not content_type.startswith("application/json"):
            return response
        # Read and rebuild streaming body
        body_chunks = [chunk async for chunk in response.body_iterator]
        body = b"".join(body_chunks)
        try:
            text = body.decode("utf-8")
            if request.url.path.endswith("/api/v1/huawei/parse"):
                AggregatedResponse.model_validate_json(text)
            elif request.url.path.endswith("/parse") and "/api/v1/huawei/" in request.url.path:
                ParseResponse.model_validate_json(text)
            # else: not enforced
        except Exception:
            # Replace with standardized error structure if validation fails
            body = json.dumps({
                "success": False,
                "error": "Response schema validation failed",
                "error_code": "SCHEMA_INVALID",
            }).encode("utf-8")
            response.status_code = 500
            response.headers["Content-Type"] = "application/json"
        return Response(content=body, status_code=response.status_code, headers=dict(response.headers))


def add_middlewares(app: FastAPI) -> None:
    app.add_middleware(RequestIDMiddleware)
    app.add_middleware(ResponseSchemaValidationMiddleware)


