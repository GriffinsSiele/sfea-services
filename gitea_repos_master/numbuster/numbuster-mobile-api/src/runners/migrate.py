import asyncio

import yaml
from mongo_client.client import MongoSessions

from src.config.settings import MONGO_COLLECTION, MONGO_DB, MONGO_URL

path = "/home/alien/Downloads/Telegram Desktop/sessions.yml"


async def run():
    with open(path, "r") as stream:
        try:
            data = yaml.safe_load(stream)
        except yaml.YAMLError as exc:
            exit()

    mongo = await MongoSessions(
        MONGO_URL, db=MONGO_DB, collection=MONGO_COLLECTION
    ).connect()

    for session in data["sessions"]:
        payload = {
            "access_token": session["token"],
            "fcm_token": session["device"],
            "host": "https://7d7992e49365310ec5e997241c6312bd.com",
            "host_updated": "2021-04-08 20:04:56.473079",
            "proxy_id": "-1",
            "meta": session["data"],
        }
        await mongo.add({"session": payload})


if __name__ == "__main__":
    asyncio.run(run())
