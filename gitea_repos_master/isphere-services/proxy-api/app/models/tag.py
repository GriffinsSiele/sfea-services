from sqlalchemy import BigInteger, Column, Sequence, String
from sqlalchemy.orm import relationship

from .base import Base


class Tag(Base):
    __tablename__ = "tag"

    id = Column(
        BigInteger,
        Sequence("tag_id_seq", start=1),
        primary_key=True,
        autoincrement=True,
    )
    name = Column(String(50), unique=True)

    proxies = relationship("Proxy", secondary="proxy_tag", back_populates="tags")
