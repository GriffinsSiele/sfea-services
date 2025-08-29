import asyncio
import logging
import random
import string

from mongo_client.client import MongoSessions
from pydash import find, get
from requests_logic.core.optional.ja3.utils.ja3_collection import ja3_collection
from worker_classes.logger import Logger

from src.config.settings import MONGO_COLLECTION, MONGO_DB, MONGO_URL
from src.logic.papajohns.search_manager import SearchPapaJohnsManager


async def run():
    Logger().create(sensitive_fields=["json:device_token", "json:ja3"])

    proxies = []
    ja3_example = [*ja3_collection]
    mongo = await MongoSessions(
        MONGO_URL, db=MONGO_DB, collection=MONGO_COLLECTION
    ).connect()

    for proxy in proxies:
        for ja3 in ja3_example:
            existed_ = list(await mongo.get_sessions())

            if find(
                existed_,
                lambda existed: get(existed, "session.proxy_id") == get(proxy, "id")
                and get(existed, "session.ja3") == ja3["Ja3"],
            ):
                print("Skip")
                continue

            try:
                substring = "".join(
                    [
                        random.choice(string.ascii_lowercase + string.digits)
                        for _ in range(22)
                    ]
                )
                p1 = "APA91bEcyUAhpaTlk9P3YikB2d"
                p2 = "-8Jm0SAg1W_rciFJvihxUzg2"
                p3 = "aFBevNutt0xeAS1FfYm"
                p4 = "Ab51k8E6WzOf71tBVg" + "CULctAoWXEW29"
                p5 = "C1fuSYMZ1YlXlSerCJQ"
                p6 = "irFKmjRTGTdBAZCIx"
                device_token = substring + f"{p1}:{p2}-{p3}-{p4}_{p5}_{p6}"

                sp = SearchPapaJohnsManager(
                    auth_data={
                        "ja3": ja3["Ja3"],
                        "user_agent": ja3["UserAgent"],
                        "cookies": None,
                        "device_token": device_token,
                        "proxy_id": get(proxy, "id"),
                    },
                )
                await sp.prepare()
                print(await sp.search("+79208533738"))
                mongo.add(
                    {
                        "session": {
                            "ja3": ja3["Ja3"],
                            "user_agent": ja3["UserAgent"],
                            "proxy_id": get(proxy, "id"),
                            "device_token": device_token,
                        }
                    }
                )
            except Exception as e:
                logging.error(e)


if __name__ == "__main__":
    asyncio.run(run())
