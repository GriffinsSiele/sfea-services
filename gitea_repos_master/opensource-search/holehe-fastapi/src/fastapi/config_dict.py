from pydantic import ConfigDict as ConfigDictOrigin


class ConfigDict(ConfigDictOrigin):
    openapi_examples: dict | None
    search_responses_examples: dict | None
