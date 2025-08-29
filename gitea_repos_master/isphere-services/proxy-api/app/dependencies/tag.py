from fastapi import Depends
from sqlalchemy.ext.asyncio import AsyncSession

from app.controllers.tag import TagController
from app.managers.tag import TagManager
from app.utils.database import DatabaseManager


class TagManagerDependence:
    def __init__(
        self, session: AsyncSession = Depends(DatabaseManager.get_async_session)
    ):
        self.manager: TagManager = TagManager(session)


class TagControllerDependence:
    def __init__(self, tag_manager: TagManagerDependence = Depends()):
        self.object: TagController = TagController(tag_manager.manager)
