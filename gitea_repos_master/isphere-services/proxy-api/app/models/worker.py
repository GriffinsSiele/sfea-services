from sqlalchemy import BigInteger, Column, Sequence, String
from sqlalchemy.orm import relationship

from .base import Base


class Worker(Base):
    __tablename__ = "worker"

    id = Column(
        BigInteger,
        Sequence("worker_id_seq", start=1),
        primary_key=True,
        autoincrement=True,
    )
    name = Column(String(100), unique=True)

    worker_proxies = relationship("ProxyUsage", back_populates="worker")
