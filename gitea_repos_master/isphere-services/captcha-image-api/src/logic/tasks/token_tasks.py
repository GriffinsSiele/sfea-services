from typing import Optional

from sqlalchemy.ext.asyncio import AsyncSession

from src.common import enums
from src.db.models import TokenTaskModel, WebsiteModel
from src.db.repositories import TokenTaskRepository

from .base import BaseTaskService


class TokenTaskService(BaseTaskService[TokenTaskRepository, TokenTaskModel]):
    async def get_idle_token(
        self, db: AsyncSession, website: WebsiteModel
    ) -> Optional[TokenTaskModel]:
        db_token = await self.repository.fetch_idle_token(db=db, website=website)
        if db_token:
            updated_token = await self.repository.update(
                db=db, db_obj=db_token, obj_in={"status": enums.TaskStatusEnum.InUse}
            )
            return updated_token
        return None


service: "TokenTaskService" = TokenTaskService(
    repository=TokenTaskRepository(TokenTaskModel)
)
