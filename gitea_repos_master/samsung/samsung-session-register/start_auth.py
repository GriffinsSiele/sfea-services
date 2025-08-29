import asyncio
import logging

from worker_classes.sentry.sentry import Sentry, sentry_remove_context
from worker_classes.thread.timing import timing

from src.config import settings
from src.config.settings import CHECK_SESSION_INTERVAL_AUTH
from src.logger.logger import Logger
from src.logic.samsung.session_manager_auth import SamsungSearchManagerAuth
from src.logic.samsung.session_manager_common import SamsungSearchManagerCommon

Logger().create()
Sentry(
    settings.SENTRY_URL, settings.MODE, custom_log_formatter=sentry_remove_context
).create()


async def infinity_cycle() -> None:
    manager = SamsungSearchManagerAuth()
    while True:
        await main(manager)
        logging.info(f"Sleep {CHECK_SESSION_INTERVAL_AUTH} seconds ...")
        await asyncio.sleep(CHECK_SESSION_INTERVAL_AUTH)


@timing("Total processing task time")
async def main(manager: SamsungSearchManagerCommon) -> None:
    await manager.run()
    await manager.stop()


if __name__ == "__main__":
    asyncio.run(infinity_cycle())
