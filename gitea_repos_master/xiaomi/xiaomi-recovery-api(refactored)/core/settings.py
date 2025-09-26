from pydantic_settings import BaseSettings
from typing import Optional


class Settings(BaseSettings):
    """Application settings"""
    
    # API Configuration
    xiaomi_base_url: Optional[str] = "https://account.xiaomi.com"
    request_timeout_seconds: int = 30
    
    # Proxy Configuration
    proxy_url: Optional[str] = None
    
    # Captcha Configuration
    captcha_timeout: int = 60
    captcha_solution_timestamp_lifetime: int = 300
    
    # Task Configuration
    task_timeout: int = 300
    
    class Config:
        env_file = ".env"
        case_sensitive = False


def get_settings() -> Settings:
    """Get application settings"""
    return Settings()