import logging
import string

from pydantic import AliasChoices, BaseModel, Field, field_validator

from src.utils.utils import now

permitted_ascii_letters = string.ascii_letters
for letter in ["I", "O", "Q", "i", "o", "q"]:
    permitted_ascii_letters = permitted_ascii_letters.replace(letter, "")
permitted_ascii_letters_and_digits = permitted_ascii_letters + string.digits
del permitted_ascii_letters


class ElPtsStatusResponse(BaseModel):
    status: str = Field(description="Состояние сервиса", examples=["ok"])
    model_config = {"json_schema_extra": {"examples": [{"status": "ok"}]}}


class ElPtsSearchDataVin(BaseModel):
    VIN: str | None = Field(
        None,
        description="VIN транспортного средства",
    )
    BodyNum: str | None = Field(
        None,
        description="Идентификационный номер транспортного средства",
    )

    @property
    def payload(self) -> str | None:
        _payload = self.VIN if self.VIN else self.BodyNum
        return _payload.upper() if _payload else None

    @field_validator("VIN")
    @classmethod
    def validate_vin(cls, vin: str) -> str:
        if not cls.is_vin_correct(vin):
            logging.info("Invalid VIN format")
            raise ValueError("Неверный формат VIN")
        return vin

    @staticmethod
    def is_vin_correct(vin: str) -> bool:
        return (
            False
            if len(vin) != 17
            else all([char in permitted_ascii_letters_and_digits for char in vin])
        )

    class Config:
        openapi_examples = {
            "VIN": {
                "summary": "Поиск по VIN номеру",
                "value": {"VIN": "LVTDD24B4PD090302"},
            },
            "BodyNum": {
                "summary": "Поиск по заводскому номеру",
                "value": {"BodyNum": "WV2ZZZ2KZ9X060366"},
            },
        }


class ElPtsSearchDataEpts(BaseModel):
    EPTS: str = Field(description="Электронный паспорт транспортного средства")

    @field_validator("EPTS")
    @classmethod
    def validate_epts(cls, epts: str) -> str:
        if len(epts) == 15 and epts.isdigit():
            return epts
        raise ValueError(
            "Неверный формат ЭПТС, допускаются только арабские цифры, 15 знаков"
        )

    @property
    def payload(self) -> str:
        return self.EPTS

    class Config:
        openapi_examples = {
            "EPTS": {
                "summary": "Поиск по электронному паспорту транспортного средства",
                "value": {"EPTS": "164302077953838"},
            }
        }


class ElPtsDataRecords(BaseModel):
    pts_type: str = Field(
        description="Вид электронного паспорта",
        validation_alias=AliasChoices("Вид электронного паспорта", "pts_type"),
    )
    status: str = Field(
        description="Статус электронного паспорта",
        validation_alias=AliasChoices("Статус электронного паспорта", "status"),
    )
    recycling: str = Field(
        description="Сведения об уплате утилизационного сбора",
        validation_alias=AliasChoices(
            "Сведения об уплате утилизационного сбора", "recycling"
        ),
    )
    custom_clearance: str = Field(
        description="Сведения о выпуске (таможенное оформление)",
        validation_alias=AliasChoices(
            "Сведения о выпуске (таможенное оформление)", "custom_clearance"
        ),
    )
    customs_restrictions: str = Field(
        description="Таможенные ограничения",
        validation_alias=AliasChoices("Таможенные ограничения", "customs_restrictions"),
    )
    restrictions: str = Field(
        description="Ограничения (обременения) за исключением таможенных (РФ)",
        validation_alias=AliasChoices(
            "Ограничения (обременения) за исключением таможенных (РФ)", "restrictions"
        ),
    )
    registration: str = Field(
        description="Сведения о последнем регистрационном действии",
        validation_alias=AliasChoices(
            "Сведения о последнем регистрационном действии", "registration"
        ),
    )


class ElPtsSearchResponse(BaseModel):
    status: str = Field(description='Статус сообщения "ok" или "error"')
    code: int = Field(description='Код сообщения, например "200", "204", "500" и иные')
    message: str = Field(description='"ok" или "<сообщение об ошибке>"')
    records: list[ElPtsDataRecords] = []
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
                            "records": [
                                {
                                    "pts_type": "Электронный паспорт транспортного средства",
                                    "status": "действующий",
                                    "recycling": "РФ уплачен",
                                    "custom_clearance": "Имеются",
                                    "customs_restrictions": "Отсутствуют",
                                    "restrictions": "СВЕДЕНИЯ ОБ ОГРАНИЧЕНИЯХ ОТСУТСТВУЮТ",
                                    "registration": "РФ - Постановка на регистрационный учет (03.05.2023)",
                                }
                            ],
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
