"""
Проверка времени жизни капчи, после ее решения.
"""

import asyncio
import logging
import time

from worker_classes.logger import Logger

from src.logic.xiaomi.xiaomi import Xiaomi


async def main():
    sleep_time = 10 * 60  # sec
    search_res = {}
    xiaomi = Xiaomi()
    try:
        await xiaomi.prepare()
        logging.info(f"Sleep for {sleep_time} seconds")
        time.sleep(sleep_time)
        search_res = await xiaomi.search("ivanov@gmail.com")
    except Exception as e:
        logging.error(e)
    await asyncio.sleep(2)
    logging.info(f"Result: {search_res}")


if __name__ == "__main__":
    Logger().create()
    asyncio.run(main())
