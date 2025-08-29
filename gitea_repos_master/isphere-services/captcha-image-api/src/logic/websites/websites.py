from typing import Any

from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy.sql import text

from src.common import exceptions, logger
from src.db.models import WebsiteModel
from src.db.repositories import website_repository


class WebsitesService:
    def __init__(self):
        self.logger = logger.Logger("WebsitesServiceLogger").get_logger()
        self.repository = website_repository

    async def get_website(
        self,
        db: AsyncSession,
        filter_kwargs: dict[str, Any],
        raise_exception: bool = True,
    ) -> WebsiteModel:
        db_website = await self.repository.get(db=db, filter_kwargs=filter_kwargs)
        if db_website is None and raise_exception:
            raise exceptions.NotFoundException(message="Website does not exist")
        return db_website

    async def get_websites(self, db: AsyncSession) -> list[WebsiteModel]:
        websites = await self.repository.get_by_filter(
            db=db,
            order_args=[
                text(f"{self.repository.model.name.name} asc"),
            ],
        )
        return websites

    async def get_or_create_website(
        self, db: AsyncSession, website_data: dict[str, Any]
    ) -> WebsiteModel:
        db_website = await self.get_website(
            db=db,
            filter_kwargs={"name": website_data["name"]},
            raise_exception=False,
        )
        if db_website is None:
            new_db_website = await self.repository.create(
                db=db,
                obj_in=website_data,
            )
            self.logger.info(f"Added website '{new_db_website.name}'")
            return new_db_website
        return db_website


service: "WebsitesService" = WebsitesService()
