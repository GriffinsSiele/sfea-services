import asyncio
from typing import Callable

from fastapi import HTTPException, status, Response

from app.controllers.base import BaseController
from app.managers.proxy_usage import ProxyUsageManager
from app.models import Worker, ProxyUsage
from app.schemas.worker import BlockProxySchema


class WorkerController(BaseController):
    @staticmethod
    def __get_gather_task(
        function: Callable, worker_id: int, proxy_ids: list[int], active: bool
    ):
        return function, {
            "where_conditions": [
                ProxyUsage.worker_id == worker_id,
                ProxyUsage.proxy_id.in_(proxy_ids),
            ],
            "active": active,
        }

    async def block_proxy(self, data: BlockProxySchema):
        worker_id = await self.manager.exists_or_none(Worker.name == data.worker)
        if not worker_id:
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail=f" Worker with name={data.worker} does not exist!",
            )

        pu_manager = ProxyUsageManager(self.manager.session)
        tasks = []
        if data.block_proxy_ids:
            tasks.append(
                WorkerController.__get_gather_task(
                    pu_manager.update, worker_id, data.block_proxy_ids, False
                )
            )
        if data.unblock_proxy_ids:
            tasks.append(
                WorkerController.__get_gather_task(
                    pu_manager.update, worker_id, data.unblock_proxy_ids, True
                )
            )
        if tasks:
            await asyncio.gather(*[f(**kwargs) for f, kwargs in tasks])
            await pu_manager.session.commit()
        return Response(status_code=status.HTTP_200_OK)
