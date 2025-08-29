from typing import Any, Optional

from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy.sql import text

from src.common import exceptions, logger
from src.db.models import SourceModel
from src.db.repositories import captcha_source_repository
from src.logic.nnetworks import nnetwork_service
from src.schemas import Source


class CaptchaSources:
    def __init__(self):
        self.logger = logger.Logger("CaptchaSources").get_logger()
        self.repository = captcha_source_repository

    async def get_source(
        self,
        db: AsyncSession,
        filter_kwargs: dict[str, Any],
        raise_exception: bool = True,
    ) -> Optional["SourceModel"]:
        db_source = await self.repository.get(db=db, filter_kwargs=filter_kwargs)
        if db_source is None and raise_exception:
            raise exceptions.NotFoundException(message="Source does not exist")
        return db_source

    async def get_sources(self, db: AsyncSession) -> list[Source]:
        db_sources = await self.repository.get_by_filter(
            db=db,
            order_args=[
                text(f"{self.repository.model.is_nnetwork_provider.name} desc"),
                text(f"{self.repository.model.name.name} asc"),
            ],
        )
        return [Source.model_validate(source) for source in db_sources]

    async def get_or_create_source(self, db: AsyncSession, source: str) -> SourceModel:
        db_source = await self.get_source(
            db=db,
            filter_kwargs={self.repository.model.name.name: source},
            raise_exception=False,
        )
        if db_source:
            return db_source
        is_nnetwork_provider = source in nnetwork_service.nnetworks_data.keys()
        new_db_source = await self.repository.create(
            db=db,
            obj_in={
                "name": source,
                "is_nnetwork_provider": is_nnetwork_provider,
            },
        )
        self.logger.info(f"Created source '{source}'")
        return new_db_source


service: "CaptchaSources" = CaptchaSources()
