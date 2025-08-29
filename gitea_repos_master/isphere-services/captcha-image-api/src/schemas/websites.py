from typing import Optional

from fastapi import Body
from pydantic import Field

from .base import APISchema, APISchemaWithExtra


class TokenRequestWebsiteDataInput(APISchemaWithExtra):
    """Input schema for website_config data when requesting tokens."""

    website_key: str = Field(default=None)
    extra_token_factor: Optional[float] = Field(
        default=None,
        ge=0,
        le=2,
    )

    def __init__(self, **kwargs):
        super().__init__(**kwargs)


class WebsiteConfig(TokenRequestWebsiteDataInput):
    """Schema for website_config data validating."""

    token_type: str = Field(default=None)
    provider: str = Field(default=None)


class WebsiteUpdate(APISchema):
    """Input/output schema for url and website_config data update"""

    url: Optional[str] = None
    name: Optional[str] = None
    max_token_pool: Optional[int] = Field(default=None, ge=1)
    min_token_pool: Optional[int] = Field(default=None, ge=0)
    website_config: Optional[WebsiteConfig] = Body(default=None)


class Website(APISchema):
    """Schema for website data output."""

    id: int
    url: str
    name: str
    min_token_pool: int
    max_token_pool: int
    current_token_pool: int
    website_config: WebsiteConfig
