from src.config.base import APISettings

api_settings = APISettings(_env_file=".env", _env_file_encoding="utf-8")  # type: ignore[call-arg]
