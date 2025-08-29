from typing import Any

from .base import WorkerContextResponseSchema


class ISphereExceptionSchema(WorkerContextResponseSchema):
    records: list[Any] = []
