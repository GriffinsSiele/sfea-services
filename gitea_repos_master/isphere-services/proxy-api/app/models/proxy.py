from sqlalchemy import (
    BigInteger,
    CHAR,
    CheckConstraint,
    Column,
    DateTime,
    Enum,
    Index,
    Integer,
    Sequence,
    String,
)
from sqlalchemy.orm import relationship

from .deleted_datetime import DeletedDateTime
from .utils import ActiveModel, ProtocolEnum


class Proxy(DeletedDateTime, ActiveModel):
    __tablename__ = "proxy"

    id = Column(
        BigInteger,
        Sequence("proxy_id_seq", start=1),
        primary_key=True,
        autoincrement=True,
    )
    host = Column(String(70), nullable=False)
    port = Column(Integer, nullable=False, default=8000)
    login = Column(
        String(100),
        nullable=True,
        comment="Optional authorization login field of user agent to a proxy server",
    )
    password = Column(
        String(100), nullable=True, comment="Password for authorization in the proxy"
    )
    protocol = Column(
        Enum(ProtocolEnum),
        nullable=False,
        default=ProtocolEnum.http.value,
        comment="Enumeration field, accessible values: http, https, socks5. "
        'For example, the field contains "https".',
    )
    country = Column(
        CHAR(2),
        nullable=False,
        default="ru",
        comment="Proxy location country, used for blocked resources (Instagram or "
        "others), proxy filtering is performing by tags field.",
    )
    provider = Column(
        String(100), nullable=True, comment='Proxy provider, like "iproxy.online"'
    )
    created = Column(DateTime, nullable=False, comment="Date and time of proxy creation")

    tags = relationship("Tag", secondary="proxy_tag", back_populates="proxies")
    proxy_workers = relationship(
        "ProxyUsage",
        back_populates="proxy",
        cascade="all, delete",
    )

    def __get_credentials(self) -> str:
        return f"{self.login}:{self.password}@" if self.login and self.password else ""

    @property
    def url(self):
        return (
            f"{self.protocol.value}://{self.__get_credentials()}{self.host}:{self.port}"
        )

    @property
    def absolute_path(self):
        return f"{self.__get_credentials()}{self.host}:{self.port}"

    __table_args__ = (
        Index("proxy_active_idx", "active"),
        Index("proxy_deleted_idx", "deleted"),
        CheckConstraint("port > 0 and port < 65536", name="check_port"),
    )
