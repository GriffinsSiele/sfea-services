from src.config.base import LoggingSettings

logging_settings = LoggingSettings(_env_file=".env", _env_file_encoding="utf-8")  # type: ignore[call-arg]
