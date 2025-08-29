from src.db.models import WebsiteModel

from .base import BaseRepository


class WebsiteRepository(BaseRepository[WebsiteModel]):
    pass


repository: "WebsiteRepository" = WebsiteRepository(WebsiteModel)
