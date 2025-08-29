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
    NODE_PATH: str = "/usr/bin/node"
    CAPTCHA_SERVICE_URL: str = ""
    CAPTCHA_PROVIDER: str = ""
    SENTRY_URL: str = ""

    model_config = SettingsConfigDict(
        env_file=env if PUtils.is_file_exists(env) else env_example,
        env_file_encoding="utf-8",
        case_sensitive=True,
        extra="ignore",
    )


settings = Settings()
