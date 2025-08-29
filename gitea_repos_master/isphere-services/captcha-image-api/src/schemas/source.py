from typing import Optional

from pydantic import Field

from src.common.enums import LanguagePoolEnum

from .base import APISchema


class SourceSolutionSpecification(APISchema):
    """Schema for source's model spolution_specification data input/output"""

    case: Optional[bool] = Field(default=None)
    phrase: Optional[bool] = Field(default=None)
    math: Optional[bool] = Field(default=None)
    numeric: Optional[int] = Field(default=None, ge=0, le=1)
    maxLength: Optional[int] = Field(default=20, ge=1, alias="max_length")
    minLength: Optional[int] = Field(default=None, ge=0, alias="min_length")
    characters: Optional[str] = Field(default=None)
    languagePool: Optional[LanguagePoolEnum] = Field(
        default=None, min_length=2, alias="language_pool"
    )


class SourceAutoModeConfig(APISchema):
    """Schema for source's model auto_mode_config data input/output"""

    min_acc: Optional[float] = Field(default=None, ge=0, le=1)
    captcha_ttl: Optional[float] = Field(default=None, ge=0)
    provider_priority: Optional[dict] = Field(
        default=None, examples=[{"antigate": 1, "rucaptcha": 3, "capmonster": 2}]
    )


class SourceConfigUpdate(APISchema):
    """Schema for source's model configs data input"""

    solution_specification: SourceSolutionSpecification
    auto_mode_config: SourceAutoModeConfig


class Source(APISchema):
    """Schema for 'Source' model data output."""

    id: int = Field(examples=[1])
    name: str = Field(examples=["getcontact"])
    is_nnetwork_provider: bool = Field(examples=[True])
    solution_specification: SourceSolutionSpecification
    auto_mode_config: SourceAutoModeConfig
