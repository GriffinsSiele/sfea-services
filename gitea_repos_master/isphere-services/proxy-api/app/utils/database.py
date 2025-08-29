from asyncio import current_task
from contextlib import asynccontextmanager
from typing import AsyncIterator

from sqlalchemy.ext.asyncio import async_scoped_session, create_async_engine, AsyncSession
from sqlalchemy.orm import sessionmaker

from app.settings import POOL_SIZE, POSTGRESQL_DB, POSTGRESQL_URL
from app.utils.metaclasses import MetaThreadSingleton


class DatabaseManager(metaclass=MetaThreadSingleton):
    def __init__(self, db_url: str, db_name: str, pool_size: int):
        self.__db_uri = f"{db_url}/{db_name}"
        self.engine = create_async_engine(
            self.__db_uri,
            pool_size=pool_size,
            pool_pre_ping=True,
            connect_args={"server_settings": {"statement_timeout": "5000"}},
        )
        self.async_session_factory = sessionmaker(
            self.engine, class_=AsyncSession, expire_on_commit=False
        )
        self.async_session = async_scoped_session(
            self.async_session_factory, scopefunc=current_task
        )

    @staticmethod
    async def get_async_session() -> AsyncIterator[AsyncSession]:
        db_manager = DatabaseManager(POSTGRESQL_URL, POSTGRESQL_DB, POOL_SIZE)
        async with db_manager.async_session() as session:
            yield session

    @staticmethod
    @asynccontextmanager
    async def with_async_session():
        db = DatabaseManager(POSTGRESQL_URL, POSTGRESQL_DB, POOL_SIZE)
        async with db.async_session_factory() as session:
            yield session

    @staticmethod
    @asynccontextmanager
    async def with_async_connection():
        db_manager = DatabaseManager(POSTGRESQL_URL, POSTGRESQL_DB, POOL_SIZE)
        async with db_manager.engine.begin() as connection:
            yield connection
