from pydash import get

from src.config.app import SearchConfig
from src.logic.adapters.item import ItemAdapter


class ResponseAdapter:
    MAX_ITEM_COUNT = SearchConfig.MAX_ITEM_COUNT_IN_RESPONSE

    @staticmethod
    def cast(payload):
        result = []
        for item_raw in get(payload, "data.items", []):
            if len(result) >= ResponseAdapter.MAX_ITEM_COUNT:
                break

            item = ItemAdapter.cast_item(item_raw)
            if item:
                result.append(item)

        return result
