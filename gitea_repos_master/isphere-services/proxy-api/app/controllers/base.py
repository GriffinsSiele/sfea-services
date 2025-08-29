from typing import TypeVar

from fastapi import Response, status

from app.managers.base import BaseManager
from app.utils.errors import NotFoundError


M = TypeVar("M", bound=BaseManager)


class BaseController:
    def __init__(self, manager: M):
        self._manager: M = manager

    @property
    def manager(self) -> M:
        return self._manager

    async def delete(self, instance_id: int) -> Response:
        try:
            await self.manager.delete(instance_id)
            await self.manager.session.commit()
        except NotFoundError:
            return Response(status_code=status.HTTP_404_NOT_FOUND)
        return Response(status_code=status.HTTP_204_NO_CONTENT)
