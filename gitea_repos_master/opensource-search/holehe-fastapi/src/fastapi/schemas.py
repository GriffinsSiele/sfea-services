"""
Модуль содержит pydantic модели для валидауии данных.
"""

from typing import List

from pydantic import BaseModel, Field
from pydash import now

from src.fastapi.config_dict import ConfigDict
from src.logic.holehe_search.modules import ACTIVE_MODULES as ACTIVE_MODULES_HOLEHE
from src.logic.ignorant_search.modules import ACTIVE_MODULES as ACTIVE_MODULES_IGNORANT


class StatusResponse(BaseModel):
    """
    Модель ответа при проверке состояния сервиса.
    """

    status: str = Field(description="Состояние сервиса", examples=["ok"])
    model_config = {"json_schema_extra": {"examples": [{"status": "ok"}]}}


class ModuleResponse(BaseModel):
    modules: List[str] = Field(description="Название модуля", examples=["instagram"])


class SearchPayload(BaseModel):
    """
    Модель входных данных для поиска информации о пользователе.
    Содержит примеры запросов.
    """

    payload: str = Field(description="Телефон/Почта")
    modules: List[str] = Field(description="Модули")
    timeout: int | None = Field(0, description="Максимальное время выполнения задачи")
    starttime: int | None = Field(0, description="Время начала выполнения задачи")

    def __str__(self) -> str:
        return f"{self.payload}: M-{len(self.modules)}"


class SearchEmailPayload(SearchPayload):
    @property
    def payload_raw(self) -> dict:
        modules = self.modules if self.modules != ["*"] else ACTIVE_MODULES_HOLEHE
        return {"payload": self.payload, "modules": list(set(modules))}

    model_config = ConfigDict(
        openapi_examples={
            "1": {
                "summary": "Поиск по 1 модулю",
                "value": {"payload": "kovinevmv@gmail.com", "modules": ["gravatar"]},
            },
            "2": {
                "summary": "Поиск по нескольким модулям",
                "value": {
                    "payload": "kovinevmv@gmail.com",
                    "modules": ["gravatar", "adobe"],
                },
            },
            "3": {
                "summary": "Поиск по всем активным модулям",
                "value": {
                    "payload": "kovinevmv@gmail.com",
                    "modules": ACTIVE_MODULES_HOLEHE,
                },
            },
            "4": {
                "summary": "Поиск по всем модулям",
                "value": {"payload": "kovinevmv@gmail.com", "modules": ["*"]},
            },
            "5": {
                "summary": "Поиск с учетом TTL",
                "value": {
                    "payload": "kovinevmv@gmail.com",
                    "modules": ["twitter", "adobe"],
                    "starttime": 1711393710,
                    "timeout": 100_000_000,
                },
            },
        }
    )


class SearchPhonePayload(SearchPayload):
    @property
    def payload_raw(self) -> dict:
        modules = self.modules if self.modules != ["*"] else ACTIVE_MODULES_IGNORANT
        return {"payload": self.payload, "modules": list(set(modules))}

    model_config = ConfigDict(
        openapi_examples={
            "1": {
                "summary": "Поиск по 1 модулю",
                "value": {"payload": "79208533738", "modules": ["instagram"]},
            },
            "2": {
                "summary": "Поиск по нескольким модулям",
                "value": {"payload": "79208533738", "modules": ["instagram", "snapchat"]},
            },
            "3": {
                "summary": "Поиск по всем активным модулям",
                "value": {"payload": "79208533738", "modules": ACTIVE_MODULES_IGNORANT},
            },
            "4": {
                "summary": "Поиск по всем модулям",
                "value": {"payload": "79208533738", "modules": ["*"]},
            },
            "5": {
                "summary": "Поиск с учетом TTL",
                "value": {
                    "payload": "79208533738",
                    "modules": ["instagram", "snapchat"],
                    "starttime": 1711393710,
                    "timeout": 100_000_000,
                },
            },
        }
    )


class DataRecords(BaseModel):
    """
    Модель ответа, содержит информацию о результатах поиска.
    """

    result: str = Field(
        description='Результат, например "Найден" или "Найден, телефон/e-mail соответствует фамилии и имени"'
    )
    result_code: str = Field(description='Код результата, например "FOUND" или "MATCHED')


class SearchResponse(BaseModel):
    """
    Модель ответа, содержит служебную информацию о результатах поиска, примеры возможных ответов.
    """

    status: str = Field(description='Статус сообщения "ok" или "error"')
    code: int = Field(description='Код сообщения, например "200", "204", "500" и иные')
    message: str = Field(description='"ok" или "<сообщение об ошибке>"')
    records: list[DataRecords] = []
    timestamp: int = Field(default_factory=now)

    model_config = ConfigDict()
