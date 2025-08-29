import asyncio

from queue_logic.client import KeyDBQueue
from worker_classes.logger import Logger

from src.config.settings import KEYDB_QUEUE, KEYDB_URL


async def main():
    Logger().create()

    kdbq = await KeyDBQueue(KEYDB_URL, service=KEYDB_QUEUE).connect()

    for i in range(10, 60):
        await kdbq.add_task(f"7920831{i}40")


if __name__ == "__main__":
    asyncio.run(main())
