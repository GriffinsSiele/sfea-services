from typing import Any, Optional

from pydantic import Field, field_validator

from .base import APISchema, WorkerContextResponseSchema


class DomruSearchInputSchema(APISchema):
    phone: str = Field(..., examples=["+79913644101"])
    timeout: int | None = Field(0, description="Максимальное время выполнения задачи")
    starttime: int | None = Field(0, description="Время начала выполнения задачи")

    @field_validator("phone")
    @classmethod
    def validate_phone(cls, value: str) -> str:
        return f"+{value}" if not value.startswith("+") else value

    class Config:
        openapi_examples = {
            "Номер телефона": {
                "summary": "Поиск по номеру телефона",
                "value": {"phone": "+79913644101"},
            },
            "С учетом TTL": {
                "summary": "Поиск по номеру телефона с учетом TTL",
                "value": {
                    "phone": "+79913644101",
                    "starttime": 1711393710,
                    "timeout": 100_000_000,
                },
            },
        }


class DomruContactDetailSchema(APISchema):
    contactId: Optional[int] = None
    agreementId: Optional[int] = None
    address: Optional[str] = None


class DomruSearchResponseSchema(WorkerContextResponseSchema):
    contacts: Optional[list[DomruContactDetailSchema]] = Field([], alias="records")

    class Config:
        search_responses_examples: dict[int | str, dict[str, Any]] | None = {
            200: {
                "description": "Normal",
                "content": {
                    "application/json": {
                        "example": {
                            "status": "ok",
                            "code": 200,
                            "message": "ok",
                            "records": [
                                {
                                    "contactId": 1234567,
                                    "agreementId": 8901234,
                                    "address": "Санкт-Петербург, A********************, ******, 11, п.1",
                                },
                            ],
                            "timestamp": 1727619145,
                        },
                    },
                },
            },
            204: {
                "description": "No Content",
                "content": {"application/json": {"example": "not have a body"}},
            },
            500: {
                "description": "InternalWorkerError",
                "content": {
                    "application/json": {
                        "example": {
                            "status": "error",
                            "code": 500,
                            "message": "Внутренняя ошибка обработчика",
                            "records": [],
                            "timestamp": 1727619145,
                        }
                    }
                },
            },
            521: {
                "description": "ProxyError",
                "content": {
                    "application/json": {
                        "example": {
                            "status": "error",
                            "code": 521,
                            "message": "Ошибка использования сервиса proxy",
                            "records": [],
                            "timestamp": 1727619145,
                        }
                    }
                },
            },
            599: {
                "description": "SourceParseError",
                "content": {
                    "application/json": {
                        "example": {
                            "status": "error",
                            "code": 599,
                            "message": "Неизвестная ошибка",
                            "records": [],
                            "timestamp": 1727619145,
                        }
                    }
                },
            },
        }
