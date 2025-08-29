from sqlalchemy import (
    BigInteger,
    CheckConstraint,
    Column,
    DateTime,
    ForeignKey,
    Index,
    Integer,
    Sequence,
)
from sqlalchemy.orm import relationship

from .utils import ActiveModel


class ProxyUsage(ActiveModel):
    __tablename__ = "proxy_usage"

    id = Column(
        BigInteger,
        Sequence("proxy_usage_id_seq", start=1),
        primary_key=True,
        autoincrement=True,
    )
    count_use = Column(
        Integer,
        nullable=False,
        default=0,
        comment="The number of requests to the proxy by the worker",
    )
    count_success = Column(
        Integer,
        nullable=False,
        default=0,
        comment="The number of successful worker requests to the proxy",
    )
    last_use = Column(
        DateTime, nullable=True, comment="Date of last proxy request via the API"
    )
    last_success = Column(
        DateTime, nullable=True, comment="Date of last successful use by the worker"
    )
    worker_id = Column(ForeignKey("worker.id"))
    proxy_id = Column(ForeignKey("proxy.id"))

    worker = relationship("Worker", back_populates="worker_proxies")
    proxy = relationship("Proxy", back_populates="proxy_workers")

    __table_args__ = (
        Index("usage_last_use_idx", "last_use"),
        Index("usage_last_use_success_idx", "last_use", "last_success"),
        Index("usage_active_idx", "active"),
        CheckConstraint("count_use >= 0", name="check_count_use"),
        CheckConstraint("count_success >= 0", name="check_count_success"),
    )
