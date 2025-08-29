from datetime import datetime, timedelta

from sqlalchemy import case, func, select
from sqlalchemy.ext.asyncio import AsyncSession

from src.common import enums, utils
from src.config.daemon_config import daemon_settings
from src.db.models import TokenTaskModel, WebsiteModel
from src.db.repositories.tasks.token_task import repository as token_task_repository
from src.logic.websites import website_service


class TokenDaemonRepository(utils.SingletonLogging):
    async def count_avg_token_usage_for_website(
        self, db: AsyncSession, website: WebsiteModel
    ) -> tuple[int, int]:
        from_date = datetime.now() - timedelta(seconds=daemon_settings.CAPTCHA_TOKEN_TTL)
        query = select(
            func.count(case((TokenTaskModel.status != enums.TaskStatusEnum.Idle, 1))),
            func.count(case((TokenTaskModel.status == enums.TaskStatusEnum.Idle, 1))),
        ).where(
            TokenTaskModel.website == website.id,
            TokenTaskModel.created_at >= from_date,
            TokenTaskModel.task_id.is_not(None),
        )
        non_idle_tokens, idle_tokens = (await db.execute(query)).all()[0]
        self.logger.info(
            f"Website '{website.name}'. USED: {non_idle_tokens}, RESERVED: {idle_tokens}"
        )
        return non_idle_tokens, idle_tokens

    async def create_task_shells(
        self, db: AsyncSession, website: WebsiteModel, amount: int
    ):
        obj_in = {
            "website": website.id,
            "provider": website.website_config["provider"],
            "status": enums.TaskStatusEnum.Idle,
        }

        tasks = await token_task_repository.bulk_create(
            db=db,
            obj_ins=[obj_in for _ in range(amount)],
        )
        return tasks

    async def update_task_shells(
        self, db: AsyncSession, tasks: list[TokenTaskModel], captcha_ids: list[int]
    ) -> None:
        params = [
            {
                "id": task.id,
                "task_id": captcha_id,
            }
            for task, captcha_id in zip(tasks, captcha_ids)
        ]
        await token_task_repository.bulk_update_by_pk(
            db=db,
            params=params,
        )

    async def update_token_pool(
        self, db: AsyncSession, website: WebsiteModel, pool: int
    ) -> WebsiteModel:
        if pool == website.current_token_pool:
            return website
        updated_website = await website_service.repository.update(
            db=db,
            db_obj=website,
            obj_in={website_service.repository.model.current_token_pool.name: pool},
        )
        self.logger.info(f"Website '{website.name}': CURRENT_TOKEN_POOL set to {pool}")
        return updated_website
