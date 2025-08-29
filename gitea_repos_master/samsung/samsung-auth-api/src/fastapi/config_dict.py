from pydantic import ConfigDict


class SamsungConfigDict(ConfigDict):
    openapi_examples: dict | None
    search_responses_examples: dict | None
