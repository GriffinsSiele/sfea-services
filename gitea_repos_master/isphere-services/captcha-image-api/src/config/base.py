import logging
import os

from pydantic_settings import BaseSettings as BasePydanticSettings


class BaseSettings(BasePydanticSettings):
    MODE: str = "dev"

    class Config:
        case_sensitive = True
        extra = "ignore"


class S3Settings(BaseSettings):
    S3_URL_PATH: str
    S3_BUCKET_MAIN: str
    S3_DEFAULT_REGION: str
    S3_ACCESS_KEY_ID: str
    S3_SECRET_ACCESS_KEY: str

    S3_PREFIX_IMAGES: str


class LoggingSettings(BaseSettings):
    LOG_LEVEL: int = logging.INFO
    IGNORE_LOG_ROUTES: set = {
        "/",
        "/docs",
        "/status",
        "/openapi.json",
        "/static/swagger-ui.css",
        "/static/swagger-ui-bundle.js",
    }
    HIDDEN_LOG_FIELDS: set = {"clientKey"}


class PostgresSettings(BaseSettings):
    POSTGRES_URL_ASYNC: str


class ProviderSettings(BaseSettings):
    ANTICAPTCHA_API_KEY: str
    CAPMONSTER_API_KEY: str
    RUCAPTCHA_API_KEY: str

    CAPMONSTER_LOCAL_URL: str

    CALLBACK_URL: str = "http://localhost/api/callback"


class TokenDaemonSettings(BaseSettings):
    CAPTCHA_TOKEN_TTL: float = 115.0  # время жизни токена, сек.
    SURVEY_CYCLE: float = 30.0  # время цикла работы token-демона, сек.


class CronJobSettings(S3Settings):
    TELEGRAM_CHAT_ID: int
    TELEGRAM_TOKEN_BOT: str
    DB_CLEANUP_TOKEN_DAYS: int = 10
    DB_CLEANUP_IMAGE_EXTERNAL_DAYS: int = 90
    DB_CLEANUP_IMAGE_NNETWORK_DAYS: int = 30
    DB_CLEANUP_CRON_RULE: str = "0 3 * * sat"
    SENTRY_URL_CLEANER: str = "http://hash@domain/projectId"


class APISettings(S3Settings):
    PROJECT_NAME: str = "CaptchaAPI"
    API_PREFIX: str = "/api"

    ROOT_PATH: str = os.path.abspath(os.curdir)
    NNETWORKS_LOCAL_STORE_PATH: str = os.path.join(ROOT_PATH, "../s3data/nnetworks")

    CHECK_SOLUTION_RECEIVED_TIMESTAMP: int = 1

    SENTRY_URL_SERVER: str = "http://hash@domain/projectId"

    S3_PREFIX_NNETWORKS: str
