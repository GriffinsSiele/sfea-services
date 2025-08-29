import asyncio

from queue_logic.client import KeyDBQueue

from src.config.settings import KEYDB_URL


async def run():
    kdbq = await KeyDBQueue(KEYDB_URL, service="test", max_allowed_reconnect=5).connect()
    for i in range(100, 166):
        await kdbq.add_task(f"79208535{i}")


if __name__ == "__main__":
    asyncio.run(run())
