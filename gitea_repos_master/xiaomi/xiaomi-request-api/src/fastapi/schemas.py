"""
Модуль содержит pydantic модели для валидации данных.
"""

from typing import List, Optional, Self

from pydantic import BaseModel, EmailStr, Field, model_validator

from src.utils import now


class XiaomiStatusResponse(BaseModel):
    """
    Модель ответа при проверке состояния сервиса.
    """

    status: str = Field(description="Состояние сервиса", examples=["ok"])
    model_config = {"json_schema_extra": {"examples": [{"status": "ok"}]}}


class XiaomiSearchData(BaseModel):
    """
    Модель входных данных для поиска информации о пользователе.
    Содержит примеры запросов.
    """

    email: EmailStr | None = Field(
        None,
        description="E-mail",
    )
    phone: str = Field(None, description="Номер телефона", min_length=11, max_length=14)
    timeout: Optional[int] = Field(0, description="Максимальное время выполнения задачи")
    starttime: Optional[int] = Field(0, description="Время начала выполнения задачи")

    @model_validator(mode="after")
    def check_too_many_params(self) -> Self:
        if self.email and self.phone:
            raise ValueError('one of "email" or "phone" must be empty')
        return self

    @property
    def payload(self) -> str | None:
        if self.email:
            return self.email
        if self.phone.startswith("+"):
            return self.phone
        return "+" + self.phone

    class Config:
        openapi_examples = {
            "E-mail": {
                "summary": "Поиск по email",
                "value": {"email": "ivanov@gmail.com"},
            },
            "Номер телефона": {
                "summary": "Поиск по номеру телефона",
                "value": {"phone": "79854684248"},
            },
            "Поиск с учетом TTL": {
                "summary": "Поиск с учетом TTL",
                "value": {
                    "phone": "79166367863",
                    "starttime": 1711393710,
                    "timeout": 100_000_000,
                },
            },
        }


class XiaomiDataRecords(BaseModel):
    """
    Модель ответа, содержит информацию о результатах поиска.
    """

    result: str = Field(description='Результат, значение "Найден"')
    result_code: str = Field(description='Код результата, значение "FOUND"')


class XiaomiSearchResponse(BaseModel):
    """
    Модель ответа, содержит служебную информацию о результатах поиска, примеры возможных ответов.
    """

    status: str = Field(description='Статус сообщения "ok" или "error"')
    code: int = Field(description='Код сообщения, например "200", "204", "500" и иные')
    message: str = Field(description='"ok" или "<сообщение об ошибке>"')
    records: List[XiaomiDataRecords] = []
    timestamp: int = Field(default_factory=now)

    class Config:
        search_responses_examples = {
            200: {
                "description": "Normal",
                "content": {
                    "application/json": {
                        "example": {
                            "status": "ok",
                            "code": 200,
                            "message": "ok",
                            "records": [{"result": "Найден", "result_code": "FOUND"}],
                            "timestamp": 1705084465,
                        }
                    }
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
                            "timestamp": 1705084465,
                        }
                    }
                },
            },
            505: {
                "description": "SourceIncorrectDataDetected",
                "content": {
                    "application/json": {
                        "example": {
                            "status": "error",
                            "code": 505,
                            "message": "Источник не может выполнить запрос по указанным данным",
                            "records": [],
                            "timestamp": 1705084465,
                        }
                    }
                },
            },
            510: {
                "description": "InternalWorkerTimeout",
                "content": {
                    "application/json": {
                        "example": {
                            "status": "error",
                            "code": 510,
                            "message": "Превышен таймаут ответа обработчика",
                            "records": [],
                            "timestamp": 1705084465,
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
                            "message": "Ошибка использования proxy",
                            "records": [],
                            "timestamp": 1705084465,
                        }
                    }
                },
            },
            502: {
                "description": "ProxyServerConnection",
                "content": {
                    "application/json": {
                        "example": {
                            "status": "error",
                            "code": 502,
                            "message": "Ошибка подключения к сервису proxy",
                            "records": [],
                            "timestamp": 1705084465,
                        }
                    }
                },
            },
            504: {
                "description": "TimeoutError",
                "content": {
                    "application/json": {
                        "example": {
                            "status": "error",
                            "code": 504,
                            "message": "Превышен таймаут запроса к источнику",
                            "records": [],
                            "timestamp": 1705084465,
                        }
                    }
                },
            },
            526: {
                "description": "SessionCaptchaDecodeError",
                "content": {
                    "application/json": {
                        "example": {
                            "status": "error",
                            "code": 526,
                            "message": "Возникла ошибка расшифровки капчи",
                            "records": [],
                            "timestamp": 1705084465,
                        }
                    }
                },
            },
            527: {
                "description": "SourceParseError",
                "content": {
                    "application/json": {
                        "example": {
                            "status": "error",
                            "code": 527,
                            "message": "При выполнении операции возникла ошибка",
                            "records": [],
                            "timestamp": 1705084465,
                        }
                    }
                },
            },
            530: {
                "description": "SourceError",
                "content": {
                    "application/json": {
                        "example": {
                            "status": "error",
                            "code": 530,
                            "message": "Ошибка со стороны источника",
                            "records": [],
                            "timestamp": 1705084465,
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
                            "timestamp": 1705084465,
                        }
                    }
                },
            },
        }
