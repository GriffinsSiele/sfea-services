from typing import Optional

from pydantic import BaseModel, ConfigDict, Field


class APISchema(BaseModel):
    """Abstract base class for API schemes"""

    model_config = ConfigDict(
        from_attributes=True,
        populate_by_name=True,
    )


class APISchemaWithExtra(BaseModel):
    """Абстрактный базовый класс для API схем, позволяющий инициализировать дополнительные атрибуты модели"""

    model_config = ConfigDict(
        from_attributes=True,
        populate_by_name=True,
        extra="allow",
    )


class SimpleApiError(APISchema):
    """A simple schema for HTTP errors."""

    message: str


class ServerStatusInfo(APISchema):
    """Schema for server status response."""

    status: str


class TaskID(APISchema):
    """A simple schema for representing captcha task ID."""

    id: Optional[int] = Field(
        default=None, examples=[123], alias="task_id", validation_alias="id"
    )
