import abc
import asyncio
from typing import Any, Generic, TypeVar

from sqlalchemy.ext.asyncio import AsyncSession

from src.common import constant, enums, utils
from src.config.api_config import api_settings
from src.config.provider_config import provider_settings
from src.db.models import TaskModel
from src.logic.provider_api import provider_api_service
from src.logic.tasks.base import BaseTaskService

TaskModelType = TypeVar("TaskModelType", bound=TaskModel)
TaskServiceType = TypeVar("TaskServiceType", bound=BaseTaskService)


class BaseSolver(Generic[TaskServiceType, TaskModelType], utils.SingletonLogging):
    def __init__(self, service: TaskServiceType):
        super().__init__()
        self.service = service
        self.callback_url_tamplate = (
            provider_settings.CALLBACK_URL + "?task_id={task_id}&task_type={task_type}"
        )

    @property
    def providers_list(self) -> list[str]:
        return [
            constant.AUTO_PROVIDER,
            constant.NNETWORKS_PROVIDER,
        ] + self.external_providers_list

    @property
    def external_providers_list(self) -> list[str]:
        return provider_api_service.clients_list

    async def get_provider_balance(self, provider: str) -> dict[str, Any]:
        return await provider_api_service.request_balance(provider=provider)

    async def retrieve_task_solution(
        self, db: AsyncSession, task: TaskModelType
    ) -> TaskModelType:
        if task.solution:
            return task
        solution = await provider_api_service.request_captcha_solution(
            provider=task.provider, captcha_id=task.task_id
        )
        formatted_solution = self.normalize_solution(solution)
        solved_task = await self.service.add_solution(
            db=db,
            task=task,
            solution=formatted_solution,
        )
        return solved_task

    async def wait_for_task_solution(
        self,
        db: AsyncSession,
        timeout: int,
        task: TaskModelType,
    ) -> TaskModelType:
        self.logger.info(
            f"Awaiting task solution for {timeout}s. TASK: {task.id}, PROVIDER: {task.provider}."
        )
        timedelta = api_settings.CHECK_SOLUTION_RECEIVED_TIMESTAMP
        while timeout > 0:
            checked_task: TaskModelType = await self.service.get_task(  # type: ignore[assignment]
                db=db,
                filter_kwargs={self.service.repository.model_pk_field_name: task.id},
            )
            if checked_task.solution is not None:
                self.logger.info(
                    f"Solution received for {checked_task.task_type} task. TASK: {checked_task.id}, PROVIDER: {checked_task.provider}, SOLUTION: {checked_task.solution}"
                )
                return checked_task
            await db.refresh(checked_task)

            await asyncio.sleep(min(timeout, timedelta))
            timeout -= timedelta

            if timeout <= 0:
                self.logger.info(
                    f"Timeout exceeded, requesting for solution. TASK: {checked_task.id}, PROVIDER: {task.provider}."
                )
                try:
                    solved_task = await self.retrieve_task_solution(
                        db=db, task=checked_task
                    )
                    return solved_task
                except Exception as exc:
                    self.logger.info(
                        f"An error occurred while requesting solution: {exc.message if hasattr(exc, 'message') else exc.__str__()}"
                    )
                    break
        return task

    @abc.abstractmethod
    async def update_task_status_and_report(
        self,
        db: AsyncSession,
        task: TaskModelType,
        solved_status: enums.TaskStatusEnum,
    ) -> dict[str, Any]:
        """Method to update status task and request task report to external provider."""
        ...

    @abc.abstractmethod
    async def process_captcha_task(
        self, *args, **kwargs
    ) -> utils.DecoderResult | TaskModelType:
        """Method to process captcha task by solving it with nnetworks or external providers."""
        ...

    @abc.abstractmethod
    async def process_callback(
        self,
        db: AsyncSession,
        task_id: int,
        data: bytes | dict[str, Any],
    ) -> None:
        """Method to process callback data and extract task solution."""
        ...

    @abc.abstractmethod
    def normalize_solution(self, solution: Any) -> Any:
        """Method to normalize received captcha solution from providers"""
        ...

    @abc.abstractmethod
    async def submit_task(self, *args, **kwargs) -> Any:
        """Method to submit task to external provider"""
        ...

    @abc.abstractmethod
    def report_task(self, *args, **kwargs) -> Any:
        """Method to normalize received captcha solution from providers"""
        ...
