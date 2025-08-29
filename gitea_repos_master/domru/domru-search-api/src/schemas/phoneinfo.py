from typing import Optional

from .base import APISchema


class PhoneInfoDataSchema(APISchema):
    phone: str
    region: list[str] = []
    city: Optional[str] = None
    region_code: Optional[int] = None
    error: Optional[str] = None
