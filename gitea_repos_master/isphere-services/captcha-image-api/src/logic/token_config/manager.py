import json
import os
from typing import Any, Optional

from src.common import exceptions, utils
from src.common.exceptions import BadRequestException


class TokenConfigManager(utils.SingletonLogging):
    def __init__(self):
        super().__init__()
        self.data = self._load_config()

    @property
    def token_types(self) -> list[str]:
        return list(self.data.keys())

    def _load_config(self) -> dict[str, Any]:
        config_path = f"{os.path.dirname(os.path.realpath(__file__))}/config.json"
        with open(config_path) as json_data:
            data = json.load(json_data)
        return data

    def _token_type_data(self, token_type: Optional[str]) -> dict[str, Any]:
        data = self.data.get(token_type)
        if not data:
            raise BadRequestException(
                f"Unable to define '{token_type}' token type. Available types: {', '.join(self.token_types)}."
            )
        return data

    def _provider_data(
        self, token_type: Optional[str], provider: Optional[str]
    ) -> dict[str, Any]:
        token_type_data = self._token_type_data(token_type=token_type)
        providers: Optional[dict[str, Any]] = token_type_data.get("providers")

        if not providers:
            raise BadRequestException(
                f"Config field 'providers' is not set for {token_type} token type."
            )

        provider_data: Optional[dict[str, Any]] = providers.get(provider)  # type: ignore[arg-type]
        if not provider_data:
            raise BadRequestException(
                f"Unknown provider '{provider}' for token type {token_type}"
            )
        return provider_data

    def _query_args(
        self, token_type: Optional[str], provider: Optional[str]
    ) -> tuple[set[str], set[str]]:
        provider_data = self._provider_data(token_type=token_type, provider=provider)
        return set(provider_data["query_args"]["required"]), set(
            provider_data["query_args"]["optional"]
        )

    def validate_args(
        self,
        input_args: set[str],
        token_type: Optional[str],
        provider: Optional[str],
        ignore: set[str] = set(),
    ) -> None:
        req_args, opt_args = self._query_args(token_type, provider)
        missing_required = req_args - input_args
        if missing_required:
            raise exceptions.BadRequestException(
                f"Validation error: Missing required arguments for '{provider}' of token_type {token_type}: {', '.join(missing_required)}"
            )
        unexpected = input_args - (req_args | opt_args | ignore)
        if unexpected:
            raise exceptions.BadRequestException(
                f"Validation error: Unexpected arguments for '{provider}' of token_type {token_type}: {', '.join(unexpected)}"
            )

    def task_type(self, token_type: Optional[str]) -> str:
        token_type_data = self._token_type_data(token_type)
        _type = token_type_data.get("type")
        if not _type:
            raise BadRequestException(
                f"Task type is not defined for '{token_type}' token type."
            )
        return _type

    def report_url_slug(
        self, token_type: Optional[str], provider: Optional[str], status: str
    ) -> Optional[str]:
        provider_data = self._provider_data(token_type=token_type, provider=provider)
        return provider_data["report"][status]


manager: TokenConfigManager = TokenConfigManager()
