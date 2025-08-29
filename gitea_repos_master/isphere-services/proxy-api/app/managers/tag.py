from sqlalchemy import select

from app.models import Tag
from app.utils.queries.filter_query import FilterQuery
from .base import BaseManager


class TagManager(BaseManager):
    model = Tag

    async def get_list(
        self,
        limit: int | None = None,
        offset: int | None = None,
        sort: list | None = None,
        _filter: dict | None = None,
        execution_options: dict | None = None,
        filter_object: FilterQuery | None = None,
    ) -> list[Tag]:
        statement = self._get_list(
            select(self.model), limit, offset, sort, False, _filter, filter_object
        )
        return (await self.session.scalars(statement)).all()
