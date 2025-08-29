import json
from typing import Any

from sqlalchemy.ext.asyncio import AsyncSession

from src.common import constant, enums
from src.db.models import TokenTaskModel, WebsiteModel
from src.logic.provider_api import provider_api_service
from src.logic.solvers.base import BaseSolver
from src.logic.tasks import token_tasks_service
from src.logic.tasks.token_tasks import TokenTaskService
from src.logic.websites import website_service


class TokenSolver(BaseSolver[TokenTaskService, TokenTaskModel]):
    def __init__(self, service: TokenTaskService):
        super().__init__(service=service)

    def normalize_solution(self, solution: dict[str, Any]) -> dict[str, Any]:
        texted_token_data = solution.get("text")
        if texted_token_data:
            try:
                return json.loads(texted_token_data)
            except json.decoder.JSONDecodeError:
                return {"token": texted_token_data}
        return solution

    async def process_captcha_task(
        self, db: AsyncSession, website_data: dict[str, Any], timeout: int
    ) -> TokenTaskModel:
        website = await website_service.get_or_create_website(
            db=db, website_data=website_data
        )

        task = await self.service.get_idle_token(db=db, website=website)
        if task:
            return task

        new_task = await self.service.repository.create(
            db=db,
            obj_in={
                "website": website.id,
                "provider": website.website_config["provider"],
                "status": enums.TaskStatusEnum.Idle,
            },
        )

        submitted_task = await self.submit_task(
            db=db,
            task=new_task,
            website=website,
        )
        checked_task = await self.wait_for_task_solution(
            db=db, timeout=timeout, task=submitted_task
        )
        if not checked_task.solution:
            return checked_task
        return await self.service.repository.update(
            db=db,
            db_obj=checked_task,
            obj_in={"status": enums.TaskStatusEnum.InUse},
        )

    async def update_task_status_and_report(
        self,
        db: AsyncSession,
        task: TokenTaskModel,
        status: enums.TaskStatusEnum,
    ) -> dict[str, Any]:
        self.logger.info(
            f"Updating task status. TASK: {task.id}, PROVIDER: {task.provider}, STATUS: {status.value}."
        )
        updated_task = await self.service.repository.update(
            db=db, db_obj=task, obj_in={"status": status}  # type: ignore[arg-type]
        )

        website = await website_service.repository.get(
            db=db,
            filter_kwargs={
                website_service.repository.model_pk_field_name: updated_task.website
            },
        )
        updated_task_dict = updated_task.dict()

        await self.report_task(
            task_data=updated_task_dict,
            status=status,
            website_config=website.website_config,
        )
        return updated_task_dict

    async def submit_task(
        self,
        db: AsyncSession,
        task: TokenTaskModel,
        website: WebsiteModel,
    ) -> TokenTaskModel:
        captcha_id = await provider_api_service.submit_token_task(
            provider=website.website_config["provider"],  # type: ignore[arg-type]
            callback_url=self.callback_url_tamplate.format(
                task_id=task.id, task_type=task.task_type
            ),
            website_data=website.dict(),
        )
        updated_task = await self.service.repository.update(
            db=db,
            db_obj=task,
            obj_in={"task_id": captcha_id},
        )
        return updated_task

    async def report_task(
        self,
        task_data: dict[str, Any],
        status: enums.TaskStatusEnum,
        website_config: dict[str, Any],
    ) -> None:
        if task_data["provider"] not in constant.SEND_REPORT_DISABLED:
            report_status = await provider_api_service.send_token_task_report(
                task_data=task_data, status=status, website_config=website_config
            )
            sent_status = (
                "successfully sent" if report_status == "success" else "not sent"
            )
            self.logger.info(
                f"Report for {website_config['token_type']} task was {sent_status}. TASK: {task_data['id']}, PROVIDER: {task_data['provider']}, STATUS: {status.value}."
            )

    async def process_callback(
        self,
        db: AsyncSession,
        task_id: int,
        data: bytes | dict[str, Any],
    ) -> None:
        task = await self.service.get_task(
            db=db,
            filter_kwargs={self.service.repository.model_pk_field_name: task_id},
        )
        self.logger.info(
            f"Processing callback data. TASK: {task.id}, PROVIDER: {task.provider}."  # type: ignore[union-attr]
        )
        solution = provider_api_service.process_callback(provider=task.provider, data=data)  # type: ignore[union-attr]
        token_data = self.normalize_solution(solution)
        await self.service.add_solution(
            db=db,
            task=task,  # type: ignore[arg-type]
            solution=token_data,
        )


solver: TokenSolver = TokenSolver(service=token_tasks_service)
