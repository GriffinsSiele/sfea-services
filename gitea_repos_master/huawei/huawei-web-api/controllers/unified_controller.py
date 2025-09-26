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
        parsed_data = await self.phone_service.parse(norm)
        
        # Extract meaningful data for response
        extracted_data = parsed_data.get("extracted_data", {}) if parsed_data else {}
        user_count = parsed_data.get("user_count", 0) if parsed_data else 0
        confidence = parsed_data.get("confidence", 0.0) if parsed_data else 0.0
        
        item = ParseItem(
            input=req.value,
            type="phone",
            normalized=norm,
            data=extracted_data,
            result="Найден" if parsed_data and parsed_data.get("status") == "found" else "Не найден",
            result_code="FOUND" if parsed_data and parsed_data.get("status") == "found" else "NOT_FOUND",
            notes=[
                f"Найдено пользователей: {user_count}",
                f"Уверенность: {confidence:.2f}",
                f"Источник: {parsed_data.get('source', 'huawei')}" if parsed_data else "Ошибка"
            ] if parsed_data else ["Ошибка парсинга"]
        )
        return ParseResponse(item=item).model_dump()

    async def parse_email(self, req: ParseRequest) -> Dict[str, Any]:
        norm = self.email_service.normalize(req.value)
        parsed_data = await self.email_service.parse(norm)
        
        # Extract meaningful data for response
        extracted_data = parsed_data.get("extracted_data", {}) if parsed_data else {}
        user_count = parsed_data.get("user_count", 0) if parsed_data else 0
        confidence = parsed_data.get("confidence", 0.0) if parsed_data else 0.0
        
        item = ParseItem(
            input=req.value,
            type="email",
            normalized=norm,
            data=extracted_data,
            result="Найден" if parsed_data and parsed_data.get("status") == "found" else "Не найден",
            result_code="FOUND" if parsed_data and parsed_data.get("status") == "found" else "NOT_FOUND",
            notes=[
                f"Найдено пользователей: {user_count}",
                f"Уверенность: {confidence:.2f}",
                f"Источник: {parsed_data.get('source', 'huawei')}" if parsed_data else "Ошибка"
            ] if parsed_data else ["Ошибка парсинга"]
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


