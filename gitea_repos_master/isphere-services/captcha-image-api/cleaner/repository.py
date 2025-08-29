from datetime import datetime
from typing import Optional, Sequence

from sqlalchemy import delete, func, select
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy.sql.expression import Select

from src.common.utils import SingletonLogging
from src.config.cron_config import cron_settings
from src.db.models import ImageTaskModel, TokenTaskModel
from src.logic.s3service import s3_service


class CleanerRepository(SingletonLogging):
    def _task_filter_query(
        self,
        query: Select,
        model: TokenTaskModel | ImageTaskModel,
        from_date: datetime,
        provider: Optional[str] = None,
    ) -> Select:
        query = query.where(model.created_at <= from_date)

        if provider:
            query = (
                query.where(model.provider != provider)
                if provider.startswith("!")
                else query.where(model.provider == provider)
            )
        return query

    async def count_tasks_to_delete(
        self,
        session: AsyncSession,
        model: TokenTaskModel | ImageTaskModel,
        from_date: datetime,
        provider: Optional[str] = None,
    ) -> int:
        count_query = self._task_filter_query(
            query=select(func.count(model.id)),
            model=model,
            from_date=from_date,
            provider=provider,
        )
        count = (await session.execute(count_query)).scalar() or 0
        self.logger.info(f"Amount of {model.__name__} records to delete: {count}")
        return count

    async def fetch_token_task_chunks(
        self, session: AsyncSession, from_date: datetime, limit: int
    ) -> Sequence[int]:
        model = TokenTaskModel
        query = self._task_filter_query(
            query=select(model.id).limit(limit), model=model, from_date=from_date  # type: ignore[arg-type]
        )
        return (await session.execute(query)).scalars().all()

    async def remove_task_chunks(
        self,
        session: AsyncSession,
        model: TokenTaskModel | ImageTaskModel,
        ids_list: Sequence[int],
    ) -> None:
        delete_query = delete(model).where(model.id.in_(ids_list))  # type: ignore[arg-type]
        delete_result = await session.execute(delete_query)
        await session.commit()
        self.logger.info(
            f"Removed {delete_result.rowcount} {model.__name__} task records."
        )

    async def get_image_task_id_and_s3filename_chunks(
        self, session: AsyncSession, from_date: datetime, limit: int, provider: str
    ) -> tuple[list[int], list[str]]:
        ids_list, s3_filenames_list = [], []
        model = ImageTaskModel
        query = self._task_filter_query(
            query=select(model.id, model.s3_filename).limit(limit),
            model=model,  # type: ignore[arg-type]
            from_date=from_date,
            provider=provider,
        )
        result = (await session.execute(query)).all()
        for row in result:
            ids_list.append(row[0])
            if row[-1]:
                s3_filename = s3_service.add_object_prefix(
                    prefix=cron_settings.S3_PREFIX_IMAGES, filename=row[-1]  # type: ignore[arg-type]
                )
                s3_filenames_list.append(s3_filename)
        return ids_list, s3_filenames_list
