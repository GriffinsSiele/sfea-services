import contextlib
from typing import Optional

from sqlalchemy import text
from sqlalchemy.ext.asyncio import AsyncSession, create_async_engine
from sqlalchemy.orm import sessionmaker

from src.config.db_config import db_settings

async_engine = create_async_engine(
    url=db_settings.POSTGRES_URL_ASYNC,
    future=True,
    pool_size=15,
    max_overflow=10,
    pool_pre_ping=True,
    connect_args={"timeout": 10},
)
async_session = sessionmaker(  # type: ignore[call-overload]
    bind=async_engine,
    class_=AsyncSession,
    expire_on_commit=False,
)  # type: ignore[call-overload]


@contextlib.asynccontextmanager
async def session_generator():
    async with async_session() as session:
        yield session


async def ping_db() -> Optional[str]:
    try:
        async with session_generator() as connection:
            await connection.execute(text("SELECT * FROM alembic_version"))
            return None
    except Exception as exc:
        return f"An error occurred: {type(exc)} {exc.__str__()}"
