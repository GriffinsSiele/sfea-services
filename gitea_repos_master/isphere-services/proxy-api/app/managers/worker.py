from app.models import Worker
from .base import BaseManager


class WorkerManager(BaseManager):
    model = Worker

    async def create(self, worker_name: str) -> Worker:
        new_worker = self.model(name=worker_name)
        self.session.add(new_worker)
        return new_worker
