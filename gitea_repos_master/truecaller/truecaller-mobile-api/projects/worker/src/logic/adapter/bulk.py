from pydash import get


class BulkAdapter:
    @staticmethod
    def cast(response):
        result = get(list(get(response, "data.0", {}).values()), "1")
        return {"data": [result]} if result else response
