from src.config.base import TokenDaemonSettings

daemon_settings = TokenDaemonSettings(_env_file=".env", _env_file_encoding="utf-8")  # type: ignore[call-arg]
