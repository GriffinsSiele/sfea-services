from fastapi import Depends
from sqlalchemy.ext.asyncio import AsyncSession

from app.controllers.proxy import ProxyController
from app.managers.proxy import ProxyManager
from app.utils.database import DatabaseManager


class ProxyManagerDependence:
    def __init__(
        self, session: AsyncSession = Depends(DatabaseManager.get_async_session)
    ):
        self.manager: ProxyManager = ProxyManager(session)


class ProxyControllerDependence:
    def __init__(self, proxy_manager: ProxyManagerDependence = Depends()):
        self.object: ProxyController = ProxyController(proxy_manager.manager)
