from datetime import datetime
from typing import Optional

from pydantic import Field, field_serializer

from src.common import enums, utils

from .base import APISchema, TaskID


class TaskInfo(TaskID):
    """A base schema for representing captcha tasks info."""

    decode_time: Optional[float] = Field(default=None, examples=[0.12345], alias="time")

    @field_serializer("decode_time")
    def serialize_time(self, time: float):
        return utils.format_float(time) if time else None


class ImageTaskInfo(TaskInfo):
    """Schema for representing image tasks info."""

    solution: Optional[str] = Field(default=None, examples=["Ww1l8R"], alias="text")


class TokenTaskInfo(TaskInfo):
    """Schema for representing token tasks info."""

    solution: Optional[dict] = Field(
        default=None,
        examples=[
            {
                "token": "03ADUVZw...UWxTAe6ncIa",
                "userAgent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)...Chrome/121.0.0.0 Safari/537.36",
                "respKey": "E0_eyJ0eXAiOiJK...y2w5_YbP8PGuJBBo",
            }
        ],
        alias="data",
    )


class TaskStatusInfo(TaskID):
    """A schema for representing captcha task info with ID, creation date and status."""

    created_at: datetime = Field(default=datetime.now())
    status: enums.TaskStatusEnum = Field(default=enums.TaskStatusEnum.Success)


class TaskStatisticInfo(APISchema):
    """A schema for representing captcha tasks statistic."""

    success: int = Field(examples=[50], alias="successful")
    fail: int = Field(examples=[100], alias="failure")
    inuse: int = Field(examples=[5], alias="undefined")
    idle: Optional[int] = Field(examples=[5])
    efficiency: float = Field(examples=[0.33333])
    avg_solution_time: Optional[float] = Field(examples=[12.34567])
    from_date: datetime = Field(examples=["2023-10-31T15:41:09.436880"])

    @field_serializer("avg_solution_time")
    def serialize_time(self, avg_solution_time: Optional[float]):
        return utils.format_float(avg_solution_time) if avg_solution_time else 0
