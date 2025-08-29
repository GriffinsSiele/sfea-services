from datetime import datetime

from pydantic import BaseModel, Field

from app.models.utils import ProtocolEnum
from app.schemas.tag import BaseTagSchema


def convert_datetime_to_iso8601(date_time: datetime) -> str:
    return date_time.strftime("%Y-%m-%dT%H:%M:%S")


class BaseProxySchema(BaseModel):
    host: str = Field(max_length=70)
    port: int = Field(ge=1, le=65535)
    login: str | None = Field(max_length=100)
    password: str | None = Field(max_length=100)
    protocol: ProtocolEnum
    country: str = Field(max_length=2)
    provider: str | None = Field(default=None, max_length=100)


class ProxyDetailSchema(BaseProxySchema):
    created: datetime
    id: int
    active: bool
    deleted: datetime | None
    url: str | None = None
    absolute_path: str | None = None
    tags: list[BaseTagSchema]

    class Config:
        orm_mode = True
        use_enum_values = True
        json_encoders = {datetime: convert_datetime_to_iso8601}
        schema_extra = {
            "example": {
                "id": 1,
                "host": "127.0.0.1",
                "port": 8080,
                "login": "username",
                "password": "secret_string",
                "protocol": ProtocolEnum.http.value,
                "country": "ru",
                "provider": "provider.ru",
                "active": False,
                "created:": "2022-10-15T15:00:00",
                "deleted:": "2023-01-10T09:00:00",
                "url": "http://username:secret_string@127.0.0.1:8080",
                "absolute_path": "username:secret_string@127.0.0.1:8080",
                "tags": [{"name": "mobile"}, {"name": "TV"}],
            }
        }


class ProxyCreateSchema(BaseProxySchema):
    tags: list[str] | None

    class Config:
        schema_extra = {
            "example": {
                "host": "127.0.0.1",
                "port": 8000,
                "login": "username",
                "password": "secret_string",
                "protocol": ProtocolEnum.http.value,
                "country": "ru",
                "provider": "provider.ru",
                "active": True,
                "tags": ["mobile", "resident"],
            }
        }


class ProxyUpdateSchema(ProxyCreateSchema):
    host: str | None = Field(max_length=70)
    port: int | None = Field(ge=1, le=65535)
    protocol: ProtocolEnum | None
    country: str | None = Field(max_length=2)

    class Config:
        schema_extra = {
            "example": {
                "host": "127.0.0.1",
                "port": 8000,
                "login": "username",
                "password": "secret_string",
                "protocol": ProtocolEnum.http.value,
                "country": "ru",
                "provider": "provider.ru",
                "tags": ["mobile", "resident"],
            }
        }
