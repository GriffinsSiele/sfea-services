from sqlalchemy import BigInteger, Column, Sequence, String

from .base import Base


class Session(Base):
    __tablename__ = "session"

    id = Column(
        BigInteger,
        Sequence("session_id_seq", start=1),
        primary_key=True,
        autoincrement=True,
    )
    login = Column(String(100), nullable=False, unique=True)
    password = Column(String(100), nullable=False)
