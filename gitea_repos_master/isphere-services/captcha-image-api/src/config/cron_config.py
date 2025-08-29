from src.config.base import CronJobSettings

cron_settings = CronJobSettings(_env_file=".env", _env_file_encoding="utf-8")  # type: ignore[call-arg]
