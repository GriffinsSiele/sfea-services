from src.db.models import SourceModel

from .base import BaseRepository


class CaptchaSourceRepository(BaseRepository[SourceModel]):
    pass


repository: "CaptchaSourceRepository" = CaptchaSourceRepository(SourceModel)
