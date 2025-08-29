import asyncio

from queue_logic.keydb import KeyDBQueue

from src.config.settings import KEYDB_URL


async def run():
    kdbq = await KeyDBQueue(KEYDB_URL, service="test").connect()
    for i in range(100, 101):
        await kdbq.add_task(f"79208532{i}")


if __name__ == "__main__":
    asyncio.run(run())
