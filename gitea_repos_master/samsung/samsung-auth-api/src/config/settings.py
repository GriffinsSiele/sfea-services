"""
Модель содержит настройки приложения которые задаются из переменных окружения среды
"""

import pathlib

from putils_logic.putils import PUtils
from pydantic_settings import BaseSettings, SettingsConfigDict

_current_file_path = pathlib.Path(__file__).parent.absolute()

base_dir = PUtils.bp(_current_file_path, "..", "..")
env = PUtils.bp(base_dir, ".env")
env_example = PUtils.bp(base_dir, ".env.example")


class Settings(BaseSettings):
    MODE: str = "dev"

    PROXY_URL: str

    MONGO_URL: str
    MONGO_DB: str
    MONGO_COLLECTION_AUTH: str
    MONGO_COLLECTION_NAME_PERSON: str

    SENTRY_URL: str = ""

    model_config = SettingsConfigDict(
        env_file=env if PUtils.is_file_exists(env) else env_example,
        env_file_encoding="utf-8",
        case_sensitive=True,
        extra="ignore",
    )

    def __init__(self, **data):
        super().__init__(**data)
        self.MONGO_COLLECTION_AUTH = f"{self.MONGO_COLLECTION_AUTH}-{self.MODE}"
        self.MONGO_COLLECTION_NAME_PERSON = (
            f"{self.MONGO_COLLECTION_NAME_PERSON}-{self.MODE}"
        )


settings = Settings()
