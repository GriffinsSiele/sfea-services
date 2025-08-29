from typing import Any, Generic, Optional, TypeVar

from sqlalchemy.ext.asyncio import AsyncSession

from src.common import exceptions, logger, utils
from src.db.models import TaskModel
from src.db.repositories import BaseRepository

TaskModelType = TypeVar("TaskModelType", bound=TaskModel)
TaskRepositoryType = TypeVar("TaskRepositoryType", bound=BaseRepository)


class BaseTaskService(Generic[TaskRepositoryType, TaskModelType], utils.SingletonLogging):
    def __init__(self, repository: TaskRepositoryType):
        super().__init__()
        self.repository = repository

    async def get_task(
        self,
        db: AsyncSession,
        filter_kwargs: dict[str, Any],
        raise_exception: bool = True,
    ) -> Optional[TaskModelType]:
        db_task = await self.repository.get(db=db, filter_kwargs=filter_kwargs)
        if db_task is None and raise_exception:
            raise exceptions.NotFoundException(message="Task does not exist")
        return db_task

    async def update_task(
        self, db: AsyncSession, filter_kwargs: dict[str, Any], obj_in: dict[str, Any]
    ) -> TaskModelType:
        db_task = await self.get_task(db=db, filter_kwargs=filter_kwargs)
        updated_db_task = await self.repository.update(
            db=db, db_obj=db_task, obj_in=obj_in  # type: ignore[arg-type]
        )
        return updated_db_task

    async def add_solution(
        self,
        db: AsyncSession,
        task: TaskModelType,
        solution: Any,
    ) -> TaskModelType:
        if task.solution:
            self.logger.info(
                f"An attempt to set solution on task {task.id} with existing solution. Skipping..."
            )
            return task
        updated_task: TaskModelType = await self.repository.update(
            db=db, db_obj=task, obj_in={"solution": solution}
        )
        self.logger.info(
            f"Added task solution. TASK: {updated_task.id}, SOLUTION: {logger.Logger.short(solution)}, TIME: {updated_task.decode_time}"
        )
        return updated_task
