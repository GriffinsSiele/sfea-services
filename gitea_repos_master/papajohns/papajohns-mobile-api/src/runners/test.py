import asyncio

from queue_logic.client import KeyDBQueue

from src.config.settings import KEYDB_URL


async def run():
    kdbq = await KeyDBQueue(KEYDB_URL, service="test").connect()
    for i in range(200, 260):
        await kdbq.add_task(f"79208313{i}")


if __name__ == "__main__":
    asyncio.run(run())
