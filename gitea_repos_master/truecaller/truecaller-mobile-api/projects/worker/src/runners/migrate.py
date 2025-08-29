import asyncio

from mongo_client.client import MongoSessions
from pydash import get, uniq_by
from requests_logic.proxy import ProxyManager
from worker_classes.logger import Logger

from src.config.settings import MONGO_COLLECTION, MONGO_DB, MONGO_URL, PROXY_URL


async def run():
    Logger().create()

    mongo = await MongoSessions(
        MONGO_URL, db=MONGO_DB, collection=MONGO_COLLECTION
    ).connect()
    mongo.default_filter = {}
    sessions = await mongo.get_sessions()

    uniq = uniq_by(sessions, "session.token")

    for i, session_token in enumerate(uniq):
        print(i, "/", len(uniq))
        session_token = session_token["session"]["token"]
        session_by_token = mongo.sessions.find(filter={"session.token": session_token})
        session_by_token = [d async for d in session_by_token]

        proxy = await ProxyManager(PROXY_URL).get_proxy({"proxygroup": "1"})
        proxy_id = get(proxy, "extra_fields.id")

        for session in session_by_token:
            await mongo.session_update(session, {"session.proxy_id": proxy_id})


if __name__ == "__main__":
    asyncio.run(run())
