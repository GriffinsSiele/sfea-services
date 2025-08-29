from typing import Any, Dict, Generic, Optional, Sequence, Type, TypeVar

from sqlalchemy import delete, select, update
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy.inspection import inspect
from sqlalchemy.sql.elements import TextClause

from src.common.logger import log_crud_operation
from src.common.utils import SingletonLogging
from src.db.models.base import BaseModel

ModelType = TypeVar("ModelType", bound=BaseModel)


class BaseRepository(Generic[ModelType], SingletonLogging):
    """CRUD object with default methods."""

    def __init__(self, model: Type[ModelType]):
        self.model: type[ModelType] = model
        super().__init__()

    @property
    def model_pk_field_name(self) -> str:
        return inspect(self.model).primary_key[0].name  # type: ignore[union-attr]

    def __base_get_query(
        self,
        filter_kwargs: Optional[dict[str, Any]] = None,
        order_args: Optional[list["TextClause"]] = None,
    ):
        query = select(self.model)
        if filter_kwargs is not None:
            for field_name, value in filter_kwargs.items():
                query = query.filter(getattr(self.model, field_name, None) == value)
        if order_args is not None:
            query = query.order_by(*order_args)
        return query

    @log_crud_operation("Create")
    async def create(
        self, db: AsyncSession, *, obj_in: dict[str, Any], with_commit: bool = True
    ) -> ModelType:
        db_obj = self.model(**obj_in)
        db.add(db_obj)
        await db.flush([db_obj])
        if with_commit:
            await db.commit()
            await db.refresh(db_obj)
        return db_obj

    async def bulk_create(
        self, db: AsyncSession, obj_ins: list[dict[str, Any]], with_commit: bool = True
    ) -> list[ModelType]:
        db_objs = [self.model(**obj_in) for obj_in in obj_ins]

        db.add_all(db_objs)
        if with_commit:
            await db.commit()
        return db_objs

    async def bulk_update_by_pk(
        self, db: AsyncSession, params: list[dict[str, Any]], with_commit: bool = True
    ) -> None:
        await db.execute(update(self.model), params)
        if with_commit:
            await db.commit()

    async def get(
        self, db: AsyncSession, filter_kwargs: dict[str, Any]
    ) -> Optional[ModelType]:
        query = self.__base_get_query(filter_kwargs=filter_kwargs)
        return (await db.execute(query)).scalar_one_or_none()

    async def get_by_filter(
        self,
        db: AsyncSession,
        *,
        filter_kwargs: Optional[dict[str, Any]] = None,
        order_args: Optional[list["TextClause"]] = None,
        offset: int = 0,
        limit: int = -1,
    ) -> Sequence[ModelType]:
        query = self.__base_get_query(filter_kwargs=filter_kwargs, order_args=order_args)

        if limit == -1:
            return (await db.execute(query)).scalars().all()

        return (await db.execute(query.offset(offset).limit(limit))).scalars().all()

    async def is_exists(
        self,
        db: AsyncSession,
        *,
        filter_kwargs: dict[str, Any],
    ) -> Optional[bool]:
        db_obj = await self.get(db=db, filter_kwargs=filter_kwargs)
        return db_obj is not None

    @log_crud_operation("Update")
    async def update(
        self,
        db: AsyncSession,
        *,
        db_obj: ModelType,
        obj_in: Dict[str, Any],
        with_commit: bool = True,
    ) -> ModelType:
        model_columns = [column.key for column in self.model.__table__.columns]  # type: ignore[attr-defined]
        for field, value in obj_in.items():
            if field in model_columns:
                setattr(db_obj, field, value)

        if with_commit:
            await db.commit()
            await db.refresh(db_obj)
        return db_obj

    @log_crud_operation("Delete")
    async def remove(
        self, db: AsyncSession, *, db_obj: ModelType, with_commit: bool = True
    ) -> Optional[ModelType]:
        await db.execute(delete(self.model).where(self.model.id == db_obj.id))
        if with_commit:
            await db.commit()
        return db_obj
