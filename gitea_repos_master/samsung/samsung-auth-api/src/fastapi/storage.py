"""
Модуль содержит код для подключения к MongoDB через Lifespan Events

https://fastapi.tiangolo.com/advanced/events/
"""

import logging
from contextlib import asynccontextmanager
from typing import AsyncGenerator

from mongo_client.client import MongoSessions
from mongo_client.connection import MongoConnectionInterface

from fastapi import FastAPI
from src.config.settings import settings


@asynccontextmanager
async def lifespan(app: FastAPI) -> AsyncGenerator:
    """Создает подключения к базе данных MongoDB

    :param app: FastAPI
    :return: None
    """
    app.state.mongo_storage_auth = await set_connect(settings.MONGO_COLLECTION_AUTH)
    app.state.mongo_storage_name_person = await set_connect(
        settings.MONGO_COLLECTION_NAME_PERSON
    )

    yield

    await app.state.mongo_storage_auth.close()
    await app.state.mongo_storage_name_person.close()
    logging.info("MongoDB connections closed")


async def set_connect(collection_name: str) -> MongoConnectionInterface:
    """Создает и возвращает подключение к базе данных MongoDB

    :param collection_name: Имя коллекции для подключения
    :return: MongoConnectionInterface
    """
    connection = await MongoSessions(
        settings.MONGO_URL,
        db=settings.MONGO_DB,
        collection=collection_name,
        max_allowed_reconnect=5,
    ).connect()
    logging.info(f'Person connect to MongoDB: "{settings.MONGO_DB}/{collection_name}"')
    return connection
