import json
import logging

from fastapi import HTTPException, status

from app.managers.base import BaseManager
from app.settings import LOGGER_NAME
from app.utils.queries.errors import QUERY_UTILS_EXCEPTIONS


logger = logging.getLogger(LOGGER_NAME)


class FilterSortMixin:
    @staticmethod
    def __load_json(
        data: str | None, msg: str = "Is not valid json"
    ) -> dict | str | list | int | None:
        if data is None:
            return None

        try:
            return json.loads(data) if data else None
        except json.JSONDecodeError:
            raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail=msg)

    async def apply_filter_sort(
        self,
        manager: BaseManager,
        limit: int,
        offset: int,
        sort: str | None = None,
        default_sorter: list[str] | None = None,
        _filter: str | None = None,
        default_filter: dict | None = None,
        overlay_filter: dict | None = None,
    ):
        sort_list = self.__load_json(sort, "The sort parameter is not valid json object")
        sort_list = sort_list if sort_list else default_sorter

        filter_dict = self.__load_json(
            _filter, "The filter parameter is not valid json object"
        )
        filter_dict = {
            **(default_filter if default_filter else {}),
            **(filter_dict if filter_dict else {}),
            **(overlay_filter if overlay_filter else {}),
        }

        try:
            # Find proxies by filter related to worker
            results = await manager.get_list(limit, offset, sort_list, filter_dict)
        except QUERY_UTILS_EXCEPTIONS as error:
            logger.warning(f"Query utils error: {error}")
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST, detail=str(error)
            )

        return results
