from fastapi import Depends, Path, status
from fastapi_utils.cbv import cbv
from fastapi_utils.inferring_router import InferringRouter

from app.dependencies.authorization import authorization_dependence
from app.dependencies.tag import TagControllerDependence
from app.models import Session
from app.schemas.tag import TagSchema, BaseTagSchema

router = InferringRouter(tags=["tags"])


@cbv(router)
class TagRouter:
    controller: TagControllerDependence = Depends()
    session_data: Session = Depends(authorization_dependence)

    @router.post("/tags")
    async def create_tag(self, tag: BaseTagSchema) -> TagSchema:
        return await self.controller.object.create_tag(tag)

    @router.get("/tags", response_model=None)
    async def get_tag_list(self) -> list[TagSchema]:
        return await self.controller.object.get_list()

    @router.delete("/tags/{tag_id}", status_code=status.HTTP_204_NO_CONTENT)
    async def delete_tag(
        self,
        tag_id: int = Path(
            ge=1, title="Tag ID", description="The ID of the tag to get", example=1
        ),
    ):
        return await self.controller.object.delete(tag_id)
