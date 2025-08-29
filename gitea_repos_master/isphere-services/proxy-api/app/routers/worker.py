from fastapi import Depends
from fastapi_utils.cbv import cbv
from fastapi_utils.inferring_router import InferringRouter

from app.dependencies.authorization import authorization_dependence
from app.dependencies.worker import WorkerControllerDependence
from app.models import Session
from app.schemas.worker import BlockProxySchema


router = InferringRouter(tags=["worker"])


@cbv(router)
class WorkerRouter:
    controller: WorkerControllerDependence = Depends()
    session_data: Session = Depends(authorization_dependence)

    @router.post("/worker/block-proxy")
    async def block_proxy(self, block_proxy: BlockProxySchema):
        return await self.controller.object.block_proxy(block_proxy)
