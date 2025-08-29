from typing import Optional

from pydantic import Field, field_serializer

from src.common import utils

from .base import APISchema


class ProviderBalance(APISchema):
    """A simple schema for representing provider balance."""

    balance: float = Field(examples=[123.456])
    currency: Optional[str] = Field(examples=["USD"])

    @field_serializer("balance")
    def serialize_time(self, balance: float):
        return utils.format_float(balance, precision=3)
