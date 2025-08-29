from typing import Optional

from pydantic import Field

from .captcha_tasks import ImageTaskInfo


class DecoderTaskOutput(ImageTaskInfo):
    """A simple schema for decoder processed image output."""

    accuracy: Optional[float] = Field(default=None, examples=[0.98765])
    provider: Optional[str] = Field(default=None, examples=["capmonster"])
