import asyncio

from worker_classes.logger import Logger

from lib.src.logic.mongo.mongo import MongoSessions
from src.config.settings import MONGO_COLLECTION, MONGO_DB, MONGO_URL
from src.logic.ok.search_manager import SearchOKManager


async def main():
    Logger().create()

    mongo = await MongoSessions(
        MONGO_URL, db=MONGO_DB, collection=MONGO_COLLECTION
    ).connect()
    session = await mongo.get_session()

    # rabbitmq = await RabbitMQPublisher(RABBITMQ_URL, RABBITMQ_QUEUE_SESSION).connect()

    sm = SearchOKManager(auth_data=session["session"], rabbitmq=None)
    await sm.prepare()
    print(sm.get_session())

    print(await sm.search({"email": "kovinevmv@gmail.com"}))
    print(sm.get_session())

    # await rabbitmq.close()


if __name__ == "__main__":
    asyncio.run(main())
