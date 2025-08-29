import abc
from datetime import datetime, timezone

from sqlalchemy import (
    REAL,
    Column,
    ColumnElement,
    DateTime,
    Enum,
    ForeignKey,
    Integer,
    String,
    event,
    extract,
    func,
)
from sqlalchemy.dialects.postgresql import JSONB
from sqlalchemy.engine.base import Connection
from sqlalchemy.ext.hybrid import hybrid_property
from sqlalchemy.ext.mutable import MutableDict
from sqlalchemy.orm.mapper import Mapper

from src.common import enums, utils
from src.config.daemon_config import daemon_settings

from .base import BaseModel


class TaskModel(object):
    id = Column(Integer, primary_key=True, autoincrement=True)
    created_at = Column(DateTime(timezone=True), default=func.now())
    task_id = Column(String, nullable=True)
    provider = Column(String(20))
    decode_time = Column(REAL(precision=6))  # type: ignore[var-annotated]
    solution = Column(String(65), nullable=True)
    status = Column(Enum(enums.TaskStatusEnum, name=enums.task_status_enum_postgresql.name))  # type: ignore[var-annotated]

    @abc.abstractmethod
    def task_type(self) -> str:
        """Get model's task type."""
        ...


class ImageTaskModel(TaskModel, BaseModel):
    __tablename__ = "image_task"

    source = Column(Integer, ForeignKey("source.id", ondelete="CASCADE"), nullable=False)
    s3_filename = Column(String(120), nullable=True)

    @property
    def task_type(self):
        return enums.TaskTypeEnum.image.value


class TokenTaskModel(TaskModel, BaseModel):
    __tablename__ = "token_task"

    solution = Column(MutableDict.as_mutable(JSONB), nullable=True)  # type: ignore[arg-type, var-annotated]
    website = Column(
        Integer, ForeignKey("website.id", ondelete="CASCADE"), nullable=False
    )

    @property
    def task_type(self):
        return enums.TaskTypeEnum.token.value

    @hybrid_property
    def is_expired(self) -> ColumnElement[bool]:
        time_diff = func.trunc(
            extract("epoch", func.now()) - extract("epoch", self.created_at)
        )
        expiration_time = daemon_settings.CAPTCHA_TOKEN_TTL + self.decode_time
        return time_diff >= expiration_time


def pre_update_listener(mapper: Mapper, connection: Connection, target: ImageTaskModel):
    if target.solution is not None and target.decode_time is None:
        time_diff = (
            datetime.now(timezone.utc) - datetime.fromisoformat(str(target.created_at))
        ).total_seconds()
        target.decode_time = utils.format_float(time_diff)


event.listen(ImageTaskModel, "before_update", pre_update_listener)
event.listen(TokenTaskModel, "before_update", pre_update_listener)
