from src.config.base import S3Settings

s3_settings = S3Settings(_env_file=".env", _env_file_encoding="utf-8")  # type: ignore[call-arg]
