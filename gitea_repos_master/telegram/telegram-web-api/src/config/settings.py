import os
from functools import lru_cache
from pydantic_settings import BaseSettings
from typing import Optional


class Settings(BaseSettings):
    """Application settings"""
    
    # Application
    MODE: str = "development"
    DEBUG: bool = True
    PORT: int = 8000
    
    # MongoDB
    MONGO_URL: str = "mongodb://localhost:27017"
    MONGO_DB: str = "telegram_api"
    MONGO_COLLECTION: str = "sessions"
    
    # KeyDB/Redis
    KEYDB_URL: str = "redis://localhost:6379"
    KEYDB_QUEUE: str = "telegram_search"
    
    # Telegram API
    TELEGRAM_API_ID: Optional[str] = None
    TELEGRAM_API_HASH: Optional[str] = None
    TELEGRAM_AUTH_KEY: Optional[str] = None
    TELEGRAM_LIVE: bool = False
    
    # Proxy
    PROXY_URL: Optional[str] = None

    # Validator (SMK-RK "Validator" IS)
    VALIDATOR_ENABLED: bool = False
    VALIDATOR_BASE_URL: Optional[str] = None
    VALIDATOR_API_KEY: Optional[str] = None
    VALIDATOR_TIMEOUT_SECONDS: int = 5
    VALIDATOR_MAX_RETRIES: int = 2
    
    # Sentry
    SENTRY_URL: Optional[str] = None
    
    # Rate Limiting
    MAX_REQUESTS_PER_HOUR: int = 100
    RATE_LIMIT_WINDOW: int = 3600
    
    # Session Management
    MAX_SEARCH_PER_DAY: int = 29
    SESSION_TIMEOUT: int = 3600
    
    class Config:
        env_file = ".env"
        env_file_encoding = "utf-8"
        case_sensitive = False


@lru_cache()
def get_settings() -> Settings:
    """Get cached settings instance"""
    return Settings()


# Backward compatibility
def get_env_var(key: str, default: str = "") -> str:
    """Get environment variable with backward compatibility"""
    return os.getenv(key, default)


# Legacy settings for backward compatibility
MODE = get_env_var("MODE", "dev")
MONGO_URL = get_env_var("MONGO_URL")
MONGO_DB = get_env_var("MONGO_DB", "telegram_api")
MONGO_COLLECTION_RAW = get_env_var("MONGO_COLLECTION", "")
MONGO_COLLECTION = MONGO_COLLECTION_RAW + "-" + MODE if MONGO_COLLECTION_RAW else f"telegram_api-{MODE}"
PROXY_URL = get_env_var("PROXY_URL")
KEYDB_URL = get_env_var("KEYDB_URL")
KEYDB_QUEUE = get_env_var("KEYDB_QUEUE", "telegram_search")
SENTRY_URL = get_env_var("SENTRY_URL")
MAX_SEARCH_PER_DAY_ = get_env_var("MAX_SEARCH_PER_DAY", "29")
MAX_SEARCH_PER_DAY = int(str(MAX_SEARCH_PER_DAY_).strip('"')) if MAX_SEARCH_PER_DAY_ else 29
