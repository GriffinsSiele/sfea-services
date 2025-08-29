import enum

from sqlalchemy import Boolean, Column

from .base import Base


class ProtocolEnum(enum.Enum):
    http = "http"
    https = "https"
    socks5 = "socks5"


class ActiveModel(Base):
    __abstract__ = True

    active = Column(Boolean, default=True, nullable=False)
