from src.config.base import ProviderSettings

provider_settings = ProviderSettings(_env_file=".env", _env_file_encoding="utf-8")  # type: ignore[call-arg]
