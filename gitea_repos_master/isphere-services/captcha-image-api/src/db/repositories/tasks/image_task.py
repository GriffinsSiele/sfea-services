from sqlalchemy import inspect

from src.common import exceptions
from src.db.models import ImageTaskModel, SourceModel
from src.db.repositories.tasks.base_task import BaseTaskRepository


class ImageTaskRepository(BaseTaskRepository[ImageTaskModel, SourceModel]):
    @property
    def parent_model(self):
        for attr, column in inspect(self.model).c.items():
            if column.name == SourceModel.__tablename__:
                return getattr(self.model, attr)
        raise exceptions.ValidationError(
            f"There is no column with name '{SourceModel.__tablename__}' correlated to {type(self.model)}"
        )


repository: "ImageTaskRepository" = ImageTaskRepository(ImageTaskModel)
