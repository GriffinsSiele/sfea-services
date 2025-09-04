from functools import lru_cache
from typing import Optional

from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    mode: str = "development"
    port: int = 8002

    validator_enabled: bool = False
    validator_base_url: Optional[str] = None
    validator_api_key: Optional[str] = None
    validator_timeout_seconds: int = 5
    validator_max_retries: int = 2

    proxy_url: Optional[str] = None
    sentry_dsn: Optional[str] = None

    # Rate limiting
    redis_url: Optional[str] = None
    rate_limit_window_seconds: int = 3600
    rate_limit_max_requests: int = 100

    model_config = SettingsConfigDict(
        env_file=".env",
        env_file_encoding="utf-8",
        case_sensitive=False,
        extra="ignore",
    )


@lru_cache()
def get_settings() -> Settings:
    return Settings()


