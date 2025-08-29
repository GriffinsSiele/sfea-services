from sqlalchemy import BigInteger, Column, ForeignKey, Sequence

from .base import Base


class ProxyTag(Base):
    __tablename__ = "proxy_tag"

    id = Column(
        BigInteger,
        Sequence("proxy_tag_id_seq", start=1),
        primary_key=True,
        autoincrement=True,
    )
    proxy_id = Column(ForeignKey("proxy.id"))
    tag_id = Column(ForeignKey("tag.id"))
