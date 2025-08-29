from functools import cached_property
from typing import Union

from sqlalchemy import delete, select, update
from sqlalchemy.engine.cursor import CursorResult
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy.sql.elements import BinaryExpression
from sqlalchemy.sql.selectable import Select

from app.models.base import Base
from app.utils.errors import NotFoundError
from app.utils.queries.filter_query import FilterQuery
from app.utils.queries.sorter_query import SorterQuery


class BaseManager:
    model = Base

    def __init__(self, db_session: AsyncSession):
        self.session: AsyncSession = db_session

    @cached_property
    def filter_object(self) -> FilterQuery:
        return FilterQuery(self.model)

    @cached_property
    def sorter_object(self) -> SorterQuery:
        return SorterQuery(self.model)

    async def get(self, instance_id: int) -> Base:
        instance = await self.session.get(self.model, instance_id)
        if not instance:
            raise NotFoundError()
        return instance

    def _get_list(
        self,
        query: Select,
        limit: int | None = None,
        offset: int | None = None,
        sort: list | None = None,
        sort_group_by: bool = False,
        _filter: dict | None = None,
        filter_object: FilterQuery | None = None,
    ) -> Select:
        statement = query
        filter_instance = filter_object if filter_object else self.filter_object
        if _filter:
            statement = filter_instance.filter(query, _filter)
        if sort:
            statement = self.sorter_object.sort(statement, sort, sort_group_by)
        if limit:
            statement = statement.limit(limit)
        if offset:
            statement = statement.offset(offset)
        return statement

    async def get_list(
        self,
        limit: int | None,
        offset: int | None,
        sort: list | None,
        _filter: dict | None,
        execution_options: dict | None = None,
        filter_object: FilterQuery | None = None,
    ):
        raise NotImplemented()

    async def get_by_conditions(self, where_conditions: list[BinaryExpression]) -> Base:
        statement = select(self.model).where(*where_conditions)
        return (await self.session.scalars(statement)).first()

    async def delete(self, id_or_conditions: Union[int | list[BinaryExpression]]):
        if isinstance(id_or_conditions, int):
            instance = await self.get(id_or_conditions)
            await self.session.delete(instance)
        else:
            statement = delete(self.model).where(*id_or_conditions)
            await self.session.execute(statement)

    async def exists_or_none(self, condition: BinaryExpression) -> int | None:
        """
        Searches for an instance by condition. Returns the instance id if it exists,
        otherwise None.
        """
        statement = select(self.model.id).where(condition)
        instance = (await self.session.scalars(statement)).first()
        return instance if instance else None

    async def update(
        self,
        where_conditions: list[BinaryExpression],
        execution_options: dict | None = None,
        **values
    ) -> CursorResult:
        """
        Updates the ProxyUsage instance. Use it like:
        manager.update(
            where_conditions=[Model.id == 1],
            field1=value1,
            field2=value2
        )
        The above example updates instance with id = 1 by changing field1 and field2.
        """
        statement = update(self.model).where(*where_conditions)
        if execution_options:
            statement = statement.execution_options(**execution_options)
        return await self.session.execute(statement.values(**values))

    async def all(self) -> list[Base]:
        return (await self.session.scalars(select(self.model))).all()

    def create(self, instance: Base) -> Base:
        self.session.add(instance)
        return instance
