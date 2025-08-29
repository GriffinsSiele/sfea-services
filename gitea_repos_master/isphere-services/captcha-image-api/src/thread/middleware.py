import json
from typing import Any, Optional

from fastapi import FastAPI
from fastapi.requests import Request
from fastapi.responses import Response
from starlette.concurrency import iterate_in_threadpool
from starlette.middleware.base import BaseHTTPMiddleware, RequestResponseEndpoint
from starlette.types import Message

from src.common.logger import Logger
from src.config.logging_config import logging_settings


class RouterLoggingMiddleware(BaseHTTPMiddleware):
    def __init__(self, app: FastAPI) -> None:
        self._logger = Logger(self.__class__.__name__).get_logger()
        super().__init__(app)

    async def _set_request_body(self, request: Request, body: bytes):
        async def receive() -> Message:
            return {"type": "http.request", "body": body}

        request._receive = receive

    def _process_request_body(
        self, req_body: bytes, content_type: Optional[str] = None
    ) -> Any:
        if content_type is not None:
            if content_type == "multipart/form-data":
                return f"...{str(req_body).split(':', 1)[-1]}"  # type: ignore[assignment]
            elif content_type == "application/json":
                try:
                    return json.loads(req_body.decode("utf-8"))
                except json.decoder.JSONDecodeError:
                    return req_body.decode()
        return str(req_body)

    async def _read_response_body(self, response: Response) -> str:
        try:
            response_body: list[bytes] = [chunk async for chunk in response.body_iterator]  # type: ignore[attr-defined]
            response.body_iterator = iterate_in_threadpool(iter(response_body))  # type: ignore[attr-defined]
            return json.loads(response_body[0].decode())
        except UnicodeDecodeError:
            return str(response_body[0])

    def log_request_body(self, request: Request, req_body: bytes):
        content_type = request.headers.get("Content-Type")
        if content_type is not None:
            content_type = content_type.split(";")[0]

        request_log_dict = {
            "Request": request.method,
            "Path": request.url.path,
            "Query-Params": dict(request.query_params),
            "Content-Type": content_type,
            "Body": self._process_request_body(req_body, content_type),
        }
        self._logger.info(Logger.format_body(request_log_dict))

    async def log_response_body(self, request: Request, response: Response):
        response_log_dict = {
            "Response": request.method,
            "Path": request.url.path,
            "Body": await self._read_response_body(response=response),
            "Status": response.status_code,
        }
        self._logger.info(Logger.format_body(response_log_dict))

    async def dispatch(
        self, request: Request, call_next: RequestResponseEndpoint
    ) -> Response:
        if request.url.path in logging_settings.IGNORE_LOG_ROUTES:
            return await call_next(request)
        req_body = await request.body()
        await self._set_request_body(request, req_body)
        self.log_request_body(request, req_body)
        response = await call_next(request)
        await self.log_response_body(request, response)
        return response
