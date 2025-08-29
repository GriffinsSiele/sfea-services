import abc
from datetime import datetime, timedelta
from typing import Generic, Optional, TypeVar

from sqlalchemy import case, func, select
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy.orm.attributes import InstrumentedAttribute

from src.common import enums, utils
from src.db.models import SourceModel, TaskModel
from src.db.repositories import BaseRepository
from src.schemas import TaskStatisticInfo

TaskModelType = TypeVar("TaskModelType", bound=TaskModel)
ParentModelType = TypeVar("ParentModelType", bound=SourceModel)


class BaseTaskRepository(
    Generic[TaskModelType, ParentModelType], BaseRepository[TaskModelType]  # type: ignore[type-var]
):
    @abc.abstractmethod
    def parent_model(self) -> "InstrumentedAttribute":
        """A method to get a task model's parent attribute"""
        ...

    async def get_statistic(
        self,
        db: AsyncSession,
        parent: Optional[ParentModelType],
        provider: Optional[str],
        period_days: int,
        period_hours: float,
    ) -> TaskStatisticInfo:
        from_date = datetime.now() - timedelta(days=period_days, hours=period_hours)
        query = select(
            func.count(
                case((self.model.status == enums.TaskStatusEnum.Success, 1))
            ).label(enums.TaskStatusEnum.Success.value.lower()),
            func.count(case((self.model.status == enums.TaskStatusEnum.Fail, 1))).label(
                enums.TaskStatusEnum.Fail.value.lower()
            ),
            func.count(case((self.model.status == enums.TaskStatusEnum.InUse, 1))).label(
                enums.TaskStatusEnum.InUse.value.lower()
            ),
            func.count(case((self.model.status == enums.TaskStatusEnum.Idle, 1))).label(
                enums.TaskStatusEnum.Idle.value.lower()
            ),
            func.avg(self.model.decode_time).label("avg_solution_time"),
        ).where(self.model.created_at >= from_date)
        if parent is not None:
            query = query.where(self.parent_model == parent.id)
        if provider is not None:
            query = query.where(self.model.provider == provider)

        result = (await db.execute(query)).all()[0]
        statistics_data = result._asdict()
        ratio_k: float = (
            result[0] / (result[0] + result[1]) if result[0] + result[1] > 0 else 0.0
        )
        return TaskStatisticInfo(
            **statistics_data, efficiency=utils.format_float(ratio_k), from_date=from_date
        )
