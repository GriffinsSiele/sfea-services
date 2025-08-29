from mongo_client.fields import MongoFields
from mongo_client.logger import logger
from mongo_client.operations import MongoOperations


class MongoConnectionInterface(MongoOperations, MongoFields):
    """Класс коннектор к БД"""

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)

    async def connect(self):
        await super().connect()
        await self._preprocess_collection()
        return self

    def switch_collection(self, collection: str):
        self.sessions = self.db[collection]

    async def _preprocess_collection(self):
        await self._create_indexes()

    async def _create_indexes(self):
        """Функция создания индексов по умолчанию"""
        if self.sessions is None:
            return

        own_indexes = {"last_use_1": [(self.last_use, 1)]}

        indexes = await self.index_information()

        current_indexes = list(indexes.keys())
        allowed_indexes = ["_id_"] + list(own_indexes.keys())

        excess_indexes = set(current_indexes) - set(allowed_indexes)
        for ind in excess_indexes:
            await self.drop_index(ind)

        for ind, value in own_indexes.items():
            if ind not in current_indexes:
                await self.create_index(value)
                logger.debug(f"Mongo created new index: {value}")
