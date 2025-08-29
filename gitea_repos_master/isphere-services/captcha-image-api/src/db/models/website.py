import re
from typing import Any

from sqlalchemy import Column, SmallInteger, String
from sqlalchemy.dialects.postgresql import JSONB
from sqlalchemy.ext.mutable import MutableDict
from sqlalchemy.orm import validates

from src.common import constant, enums, exceptions
from src.logic.token_config import token_config_manager

from .base import BaseModel


class WebsiteModel(BaseModel):
    __tablename__ = "website"

    url = Column(String(2048), nullable=False)
    name = Column(String(50), unique=True, nullable=False)
    max_token_pool = Column(SmallInteger, default=20, nullable=False)
    min_token_pool = Column(SmallInteger, default=0, nullable=False)
    current_token_pool = Column(SmallInteger, default=1, nullable=False)
    website_config = Column(MutableDict.as_mutable(JSONB), nullable=False)  # type: ignore[arg-type, var-annotated]

    @validates("url")
    def validate_url(self, _, value: str):
        if not value:
            raise exceptions.ValidationError(
                f"No value for 'url' parameter was provided."
            )
        pattern = "^https?:\\/\\/(?:www\\.)?[-a-zA-Z0-9@:%._\\+~#=]{1,256}\\.[a-zA-Z0-9()]{1,6}\\b(?:[-a-zA-Z0-9()@:%_\\+.~#?&\\/=]*)$"
        if not re.match(pattern, value):
            raise exceptions.ValidationError("Provided URL-address is invalid.")
        return value

    @validates("min_token_pool")
    def validate_min(self, _, value: int):
        if value > self.max_token_pool:
            raise exceptions.ValidationError(
                f"Parameter 'max_token_pool' ({self.max_token_pool}) must be equal or greater than 'min_token_pool' ({value})"
            )
        return value

    @validates("website_config")
    def validate_website_config(self, _, value: dict[str, Any]):
        token_type = value.get("token_type")
        provider = value.get("provider")

        if not any([token_type, provider]):
            raise exceptions.ValidationError(
                f"Parameters 'provider' and 'token_type' are required for website data initialization."
            )

        token_config_manager.validate_args(
            input_args=set(value.keys()),
            token_type=token_type,
            provider=provider,
            ignore={"token_type", "extra_token_factor", "provider"},
        )

        if not enums.ExternalProviderEnum.has_value(provider):
            raise exceptions.ValidationError(
                f"Parameter 'provider' must be must be set as a value of {enums.ExternalProviderEnum.as_list()}"
            )

        if token_type not in token_config_manager.token_types:
            raise exceptions.ValidationError(
                f"Parameter 'token_type' must be must be set as a value of {token_config_manager.token_types}"
            )

        min_score = value.get("min_score")
        if (
            min_score is not None
            and provider == enums.ExternalProviderEnum.antigate.value
            and min_score not in constant.ANTIGATE_MIN_SCORE_VALIDS
        ):
            raise exceptions.ValidationError(
                f"Parameter 'min_score' must be set as a value of {constant.ANTIGATE_MIN_SCORE_VALIDS} for 'recaptchaV3' token tasks."
            )

        return value
