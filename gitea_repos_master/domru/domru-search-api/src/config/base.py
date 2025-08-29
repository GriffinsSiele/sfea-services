import pathlib

from putils_logic import PUtils
from pydantic_settings import BaseSettings as BasePydanticSettings

_current_file_path = pathlib.Path(__file__).parent.absolute()
base_dir = PUtils.bp(_current_file_path, "..", "..")
env = PUtils.bp(base_dir, ".env")


class BaseSettings(BasePydanticSettings):

    class Config:
        case_sensitive = True
        extra = "ignore"


class ProxySettings(BaseSettings):
    PROXY_RATE_UPDATE: int = 50
    PROXY_URL: str


class SentrySettings(BaseSettings):
    SENTRY_URL: str


class LoggingSettings(BaseSettings):
    IGNORE_LOG_ROUTES: set = {
        "/",
        "/docs",
        "/status",
        "/openapi.json",
        "/static/swagger-ui.css",
        "/static/swagger-ui-bundle.js",
    }


class APISettings(ProxySettings, SentrySettings, LoggingSettings):
    MODE: str = "dev"

    PROJECT_NAME: str = "domru-search-api"

    DOMRU_SEARCH_URL: str = (
        "https://api-profile.web-api.dom.ru/v1/unauth/contract-asterisked"
    )

    PHONEINFO_URL: str

    SEARCH_TASK_TIMEOUT: int = 30


settings = APISettings(_env_file=env, _env_file_encoding="utf-8")  # type: ignore[call-arg]
