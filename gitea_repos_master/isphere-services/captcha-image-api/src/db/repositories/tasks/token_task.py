from typing import Optional

from sqlalchemy import inspect, select
from sqlalchemy.ext.asyncio import AsyncSession

from src.common import enums, exceptions
from src.db.models import TokenTaskModel, WebsiteModel
from src.db.repositories.tasks.base_task import BaseTaskRepository


class TokenTaskRepository(BaseTaskRepository[TokenTaskModel, WebsiteModel]):  # type: ignore[type-var]
    @property
    def parent_model(self):
        for attr, column in inspect(self.model).c.items():
            if column.name == WebsiteModel.__tablename__:
                return getattr(self.model, attr)
        raise exceptions.ValidationError(
            f"There is no column with name '{WebsiteModel.__tablename__}' correlated to {type(self.model)}"
        )

    async def fetch_idle_token(
        self, db: AsyncSession, website: WebsiteModel
    ) -> Optional[TokenTaskModel]:
        query = (
            select(self.model)
            .where(
                self.model.status == enums.TaskStatusEnum.Idle,
                self.model.website == website.id,
                self.model.is_expired.is_(False),
                self.model.solution.isnot(None),
            )
            .order_by(self.model.created_at)
        )
        result = (await db.execute(query)).first()
        return result[0] if result else None


repository: "TokenTaskRepository" = TokenTaskRepository(TokenTaskModel)
