from fastapi import Depends
from sqlalchemy.ext.asyncio import AsyncSession

from app.controllers.worker import WorkerController
from app.managers.worker import WorkerManager
from app.utils.database import DatabaseManager


class WorkerManagerDependence:
    def __init__(
        self, session: AsyncSession = Depends(DatabaseManager.get_async_session)
    ):
        self.manager: WorkerManager = WorkerManager(session)


class WorkerControllerDependence:
    def __init__(self, worker_manager: WorkerManagerDependence = Depends()):
        self.object: WorkerController = WorkerController(worker_manager.manager)
