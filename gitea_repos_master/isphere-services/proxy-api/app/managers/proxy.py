from functools import cached_property

from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy.orm import joinedload, selectinload

from app.models import Proxy, ProxyUsage, Tag, ProxyTag
from app.utils.errors import NotFoundError
from app.utils.queries.filter_query import FilterQuery
from app.utils.queries.sorter_query import SorterQuery
from .base import BaseManager


class ProxyManager(BaseManager):
    model = Proxy

    def __init__(self, db_session: AsyncSession):
        super().__init__(db_session)

    @cached_property
    def filter_object(self) -> FilterQuery:
        return FilterQuery(
            Proxy,
            {
                "proxy_tag": {
                    "model": ProxyTag,
                    "available": False,
                    "join": {"isouter": True},
                },
                "tags": {"model": Tag},
                "proxy_usage": {"model": ProxyUsage},
            },
        )

    @cached_property
    def filter_object_tags_join(self) -> FilterQuery:
        return FilterQuery(Proxy)

    @cached_property
    def sorter_object(self) -> SorterQuery:
        return SorterQuery(Proxy, {"proxy_usage": ProxyUsage, "tags": Tag})

    async def get(self, instance_id: int) -> Proxy:
        instance = await self.session.execute(
            select(self.model)
            .where(self.model.id == instance_id)
            .options(joinedload(self.model.tags))
        )
        instance = instance.scalars().first()
        if not instance:
            raise NotFoundError()
        return instance

    async def get_list(
        self,
        limit: int | None,
        offset: int | None,
        sort: list | None,
        _filter: dict | None,
        filter_object: FilterQuery | None = None,
        execution_options: dict | None = None,
    ) -> list[Proxy]:
        statement = self._get_list(
            select(self.model), limit, offset, sort, True, _filter, filter_object
        )
        if execution_options:
            statement = statement.execution_options(**execution_options)
        statement = statement.group_by(Proxy.id, Proxy.deleted).options(
            selectinload(self.model.tags)
        )
        return (await self.session.scalars(statement)).all()

    async def get_all_ids(self) -> list[int]:
        return (
            await self.session.scalars(
                select(self.model.id).execution_options(include_deleted=True)
            )
        ).all()
