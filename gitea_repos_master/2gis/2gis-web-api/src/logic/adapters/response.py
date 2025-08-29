from pydash import get

from src.config.app import SearchParams
from src.logic.adapters.item import ItemAdapter


class ResponseAdapter:
    MAX_ITEM_COUNT = SearchParams.MAX_ITEM_COUNT_IN_RESPONSE

    @staticmethod
    def cast(payload):
        result = []
        for item_raw in get(payload, "result.items", []):
            if len(result) >= ResponseAdapter.MAX_ITEM_COUNT:
                break

            item = ItemAdapter.cast_item(item_raw)
            if item:
                result.append(item)

        return result
