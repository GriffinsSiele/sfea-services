import yaml

from fastapi import HTTPException, status

from app.settings import ALIASES_CONFIG
from app.utils.metaclasses import MetaSingleton


class AliasesLoader(metaclass=MetaSingleton):
    aliases: dict | None = None

    def __init__(self):
        if self.aliases is None:
            with open(ALIASES_CONFIG, "r") as file:
                self.aliases = yaml.safe_load(file)

    def get(self, alias_name: str):
        return self.aliases.get(alias_name, {}) if self.aliases else {}


class FilterAlias:
    def __init__(self, aliases: dict):
        """
        The aliases dictionary determines the alias name and dictionary of a sort string
        and filter string. It should have the structure similar to:
        {
            "mobile": {
                "sort": '["proxy_usage.count_use", "proxy_usage.last_success$NF"]',
                "filter": '{"tags": {"name": ["mobile"]}}',
                "limit": 1,
                "offset": 0,
            }
        }
        """
        self.aliases = aliases

    def get_filter(
        self,
        alias_name: str | None,
        sort: str | None,
        filter_dict: str | None,
        limit: int | None,
        offset: int | None,
    ) -> tuple[str, str, int, int]:
        """
        Searches the alias name in the alias dictionary. Returns a sort string and a
        filter string if the alias name is defined in the alias dictionary. Otherwise
        returns the passed parameters.
        """
        if not alias_name:
            return sort, filter_dict, limit, offset

        alias_data = self.aliases.get(alias_name)
        if not alias_data:
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail=f"The alias '{alias_name}' does not exist!",
            )
        return (
            alias_data.get("sort"),
            alias_data.get("filter"),
            alias_data.get("limit"),
            alias_data.get("offset"),
        )
