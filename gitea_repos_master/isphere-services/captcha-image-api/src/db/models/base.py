from typing import Any

from sqlalchemy import Column, DateTime, Integer
from sqlalchemy.orm import as_declarative
from sqlalchemy.sql import func


@as_declarative()
class BaseModel:
    __name__: str

    id = Column(Integer, primary_key=True, autoincrement=True)
    created_at = Column(DateTime(timezone=True), default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())

    def dict(self) -> dict[str, Any]:
        data = dict(self.__dict__)
        data.pop("_sa_instance_state", None)
        return data
