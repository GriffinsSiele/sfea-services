from fastapi import HTTPException, status

from app.controllers.base import BaseController
from app.managers.tag import TagManager
from app.models import Tag
from app.schemas.tag import BaseTagSchema


class TagController(BaseController):
    @property
    def manager(self) -> TagManager:
        return super().manager

    async def create_tag(self, tag: BaseTagSchema):
        existing_tag = await self.manager.get_by_conditions([Tag.name == tag.name])
        if existing_tag:
            raise HTTPException(
                status_code=status.HTTP_409_CONFLICT, detail="Tag already exists"
            )
        tag = self.manager.create(Tag(name=tag.name))
        await self.manager.session.commit()
        return tag

    async def get_list(self) -> list[Tag]:
        return await self.manager.get_list()
