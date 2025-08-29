import asyncio
import json

from queue_logic.client import KeyDBQueue

from src.config.settings import KEYDB_URL


async def run():
    kdbq = KeyDBQueue(KEYDB_URL, service="test")
    await kdbq.connect()

    for i in range(10, 11):
        data = {
            "id": 276954168,
            "key": "some-key",
            "starttime": 1711206405,
            "timeout": 99999999999,
            "phone": "79208313140",
        }
        await kdbq.add_task(json.dumps(data))


if __name__ == "__main__":
    asyncio.run(run())
