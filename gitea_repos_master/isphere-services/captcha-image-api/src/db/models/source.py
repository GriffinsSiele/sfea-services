import json
from typing import Any

from sqlalchemy import Boolean, Column, String, text
from sqlalchemy.dialects.postgresql import JSONB
from sqlalchemy.ext.mutable import MutableDict
from sqlalchemy.orm import validates

from src.common import constant, exceptions, utils

from .base import BaseModel


class SourceModel(BaseModel):
    __tablename__ = "source"

    name = Column(String, unique=True, nullable=False)
    is_nnetwork_provider = Column(Boolean, default=False, nullable=False)
    solution_specification = Column(MutableDict.as_mutable(JSONB), nullable=False, server_default=text(f"'{json.dumps(constant.DEFAULT_SOLUTION_SPECIFICATION)}'"))  # type: ignore[arg-type, var-annotated]
    auto_mode_config = Column(MutableDict.as_mutable(JSONB), nullable=False, server_default=text(f"'{json.dumps(constant.DEFAULT_AUTO_MODE_CONFIG)}'"))  # type: ignore[arg-type, var-annotated]

    @validates("solution_specification")
    def validate_solution_specification(self, _, value: dict[str, Any]) -> Any:
        max_length = value.get("maxLength")
        min_length = value.get("minLength")
        if max_length is not None and min_length is not None:
            if max_length < min_length:
                raise exceptions.ValidationError(
                    f"Parameter 'max_length' ({max_length}) must be greater than 'min_length' ({min_length})"
                )
        return value

    @utils.classproperty
    def default_solution_specification(cls) -> dict[str, Any]:
        return constant.DEFAULT_SOLUTION_SPECIFICATION

    @utils.classproperty
    def default_auto_mode_config(cls) -> dict[str, Any]:
        return constant.DEFAULT_AUTO_MODE_CONFIG

    @property
    def provider_priority_queue(self) -> list[str]:
        priority = self.auto_mode_config.get(
            "provider_priority"
        ) or self.default_auto_mode_config.get("provider_priority")
        return [
            k for k, v in sorted(priority.items(), key=lambda item: item[1]) if v >= 0
        ]
