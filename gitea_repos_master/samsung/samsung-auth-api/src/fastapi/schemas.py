"""
Модуль содержит pydantic модели для валидауии данных.
"""

import logging

from pydantic import BaseModel, EmailStr, Field, model_validator
from typing_extensions import Self

from src.fastapi.config_dict import SamsungConfigDict
from src.fastapi.examples import (
    search_responses_examples,
    search_responses_examples_name,
    search_responses_examples_with_email,
)
from src.utils import now


class SamsungStatusResponse(BaseModel):
    """
    Модель ответа при проверке состояния сервиса.
    """

    status: str = Field(description="Состояние сервиса", examples=["ok"])
    model_config = {"json_schema_extra": {"examples": [{"status": "ok"}]}}


class SamsungSearchDataAuth(BaseModel):
    """
    Модель входных данных для поиска информации о пользователе.
    Содержит примеры запросов.
    """

    email: EmailStr = Field(description="E-mail")
    timeout: int | None = Field(0, description="Максимальное время выполнения задачи")
    starttime: int | None = Field(0, description="Время начала выполнения задачи")

    @property
    def payload(self) -> str:
        return self.email

    def __str__(self) -> str:
        return self.email

    model_config = SamsungConfigDict(
        openapi_examples={
            "E-mail": {
                "summary": "Поиск по email",
                "value": {"email": "baba@yandex.ru"},
            },
            "Поиск с учетом TTL": {
                "summary": "Поиск с учетом TTL",
                "value": {
                    "email": "baba@yandex.ru",
                    "starttime": 1711393710,
                    "timeout": 100_000_000,
                },
            },
        }
    )


class SamsungSearchDataPerson(BaseModel):
    """
    Модель входных данных для поиска информации о пользователе.
    Содержит примеры запросов.
    """

    first_name: str = Field(description="Имя")
    last_name: str = Field(description="Фамилия")
    birthdate: str = Field(description="День рождения в формате 20.01.1990")
    timeout: int | None = Field(0, description="Максимальное время выполнения задачи")
    starttime: int | None = Field(0, description="Время начала выполнения задачи")

    @property
    def payload(self) -> dict:
        return {
            "first_name": self.first_name,
            "last_name": self.last_name,
            "birthdate": self.birthdate,
        }

    def __str__(self) -> str:
        return f"{self.first_name} {self.last_name} {self.birthdate}"

    model_config = SamsungConfigDict(
        openapi_examples={
            "Person": {
                "summary": "Поиск пользователя по ФИО и дате рождения",
                "value": {
                    "first_name": "Иван",
                    "last_name": "Иванов",
                    "birthdate": "01.01.2000",
                },
            },
            "Поиск с учетом TTL": {
                "summary": "Поиск пользователя по ФИО и дате рождения с учетом TTL",
                "value": {
                    "first_name": "Иван",
                    "last_name": "Ivanov",
                    "birthdate": "01.01.2000",
                    "starttime": 1711393710,
                    "timeout": 100_000_000,
                },
            },
        }
    )


class SamsungSearchDataName(BaseModel):
    """
    Модель входных данных для поиска информации о пользователе.
    Содержит примеры запросов.
    """

    email: EmailStr | None = Field(
        None,
        description="E-mail",
    )
    phone: str | None = Field(
        None, description="Номер телефона", min_length=11, max_length=14
    )
    first_name: str = Field(description="Имя")
    last_name: str = Field(description="Фамилия")
    birthdate: str = Field(description="День рождения в формате 20.01.1990")
    timeout: int | None = Field(0, description="Максимальное время выполнения задачи")
    starttime: int | None = Field(0, description="Время начала выполнения задачи")

    @model_validator(mode="after")
    def check_phone_and_email(self) -> Self:
        if self.email and self.phone:
            logging.warning("Too many parameters, send your email or phone number.")
            raise ValueError
        if not self.email and not self.phone:
            logging.warning("Too few parameters, send your email or phone number.")
            raise ValueError
        return self

    @property
    def payload(self) -> dict:
        return {
            "account_login": self.email if self.email else self.phone,
            "first_name": self.first_name,
            "last_name": self.last_name,
            "birthdate": self.birthdate,
        }

    def __str__(self) -> str:
        return f"{self.email if self.email else self.phone} {self.first_name} {self.last_name} {self.birthdate}"

    model_config = SamsungConfigDict(
        openapi_examples={
            "E-main": {
                "summary": "Поиск пользователя по e-mail, ФИО и дате рождения",
                "value": {
                    "email": "i.ivanov@mail.ru",
                    "first_name": "Ivan",
                    "last_name": "Ivanov",
                    "birthdate": "10.01.1990",
                },
            },
            "Номер телефона": {
                "summary": "Поиск пользователя по номеру телефона, ФИО и дате рождения",
                "value": {
                    "phone": "79025060483",
                    "first_name": "Денис",
                    "last_name": "Денисов",
                    "birthdate": "16.07.1981",
                },
            },
            "Поиск с учетом TTL": {
                "summary": "Поиск пользователя по номеру телефона, ФИО и дате рождения с учетом TTL",
                "value": {
                    "phone": "79025060483",
                    "first_name": "Денис",
                    "last_name": "Денисов",
                    "birthdate": "16.07.1981",
                    "starttime": 1711393710,
                    "timeout": 100_000_000,
                },
            },
        }
    )


class SamsungDataRecords(BaseModel):
    """
    Модель ответа, содержит информацию о результатах поиска.
    """

    result: str = Field(
        description='Результат, например "Найден" или "Найден, телефон/e-mail соответствует фамилии и имени"'
    )
    result_code: str = Field(description='Код результата, например "FOUND" или "MATCHED')


class SamsungDataRecordsWithEmail(SamsungDataRecords):
    """
    Модель ответа с дополнительным полем e-mail
    """

    emails: list = Field([], description="Список e-mail пользователя")


class SamsungSearchResponse(BaseModel):
    """
    Модель ответа, содержит служебную информацию о результатах поиска, примеры возможных ответов.
    """

    status: str = Field(description='Статус сообщения "ok" или "error"')
    code: int = Field(description='Код сообщения, например "200", "204", "500" и иные')
    message: str = Field(description='"ok" или "<сообщение об ошибке>"')
    records: list[SamsungDataRecords] = []
    timestamp: int = Field(default_factory=now)

    model_config = SamsungConfigDict(search_responses_examples=search_responses_examples)


class SamsungSearchResponseWithEmails(SamsungSearchResponse):
    """
    Модель ответа со списком e-mail, содержит служебную информацию о результатах поиска, примеры возможных ответов.
    """

    records: list[SamsungDataRecordsWithEmail] = []

    model_config = SamsungConfigDict(
        search_responses_examples=search_responses_examples_with_email
    )


class SamsungSearchResponseName(SamsungSearchResponse):
    """
    Модель ответа со списком e-mail, содержит служебную информацию о результатах поиска, примеры возможных ответов.
    """

    records: list[SamsungDataRecords] = []

    model_config = SamsungConfigDict(
        search_responses_examples=search_responses_examples_name
    )
