from typing import Optional

from pydantic import BaseModel, ConfigDict, Field

from src.common.utils import now


class APISchema(BaseModel):
    model_config = ConfigDict(
        from_attributes=True,
        populate_by_name=True,
    )


class WorkerContextResponseSchema(APISchema):
    status: str
    code: int
    message: Optional[str] = None
    timestamp: int = Field(default_factory=now)
