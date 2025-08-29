from src.config.base import PostgresSettings

db_settings = PostgresSettings(_env_file=".env", _env_file_encoding="utf-8")  # type: ignore[call-arg]
