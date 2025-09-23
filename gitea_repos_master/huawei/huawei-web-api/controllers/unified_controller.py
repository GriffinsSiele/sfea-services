from datetime import datetime, timezone
from typing import Dict, Any

from fastapi import Depends

from api.v1.schemas.service_schemas import ParseRequest, ParseResponse, ParseItem
from core.settings import get_settings
from domain.services.phone_service import PhoneParseService
from domain.services.email_service import EmailParseService
from infrastructure.validator.client import ValidatorClient
from infrastructure.services.huawei_client import HuaweiServiceClient


class UnifiedController:
    def __init__(self, phone_service: PhoneParseService, email_service: EmailParseService):
        self.phone_service = phone_service
        self.email_service = email_service
        self.validator = ValidatorClient()
        self.huawei = HuaweiServiceClient()

    async def parse_phone(self, req: ParseRequest) -> Dict[str, Any]:
        norm = self.phone_service.normalize(req.value)
        upstream = await self.huawei.parse_phone(norm)
        data = upstream.get("records") if upstream.get("found") else None
        item = ParseItem(
            input=req.value,
            type="phone",
            normalized=norm,
            data=data,
            result="Найден" if data else "Не найден",
            result_code="FOUND" if data else "NOT_FOUND",
        )
        return ParseResponse(item=item).model_dump()

    async def parse_email(self, req: ParseRequest) -> Dict[str, Any]:
        norm = self.email_service.normalize(req.value)
        upstream = await self.huawei.parse_email(norm)
        data = upstream.get("records") if upstream.get("found") else None
        item = ParseItem(
            input=req.value,
            type="email",
            normalized=norm,
            data=data,
            result="Найден" if data else "Не найден",
            result_code="FOUND" if data else "NOT_FOUND",
        )
        return ParseResponse(item=item).model_dump()

    async def parse_unified(self, req: ParseRequest) -> Dict[str, Any]:
        detected = await self.validator.detect(req.value)
        if detected == "phone":
            return await self.parse_phone(req)
        if detected == "email":
            return await self.parse_email(req)
        item = ParseItem(
            input=req.value,
            type="unknown",
            normalized=None,
            data=None,
            result="Не найден",
            result_code="NOT_FOUND",
            notes=["Unsupported input type"],
        )
        return ParseResponse(item=item).model_dump()


def get_unified_controller(
    phone_service: PhoneParseService = Depends(PhoneParseService),
    email_service: EmailParseService = Depends(EmailParseService),
) -> UnifiedController:
    return UnifiedController(phone_service, email_service)


