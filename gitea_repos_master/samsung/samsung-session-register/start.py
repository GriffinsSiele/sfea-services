import asyncio

from worker_classes.logger import Logger
from worker_classes.sentry.sentry import Sentry, sentry_remove_context

from src.config import settings
from src.logic.samsung.samsung_manager import SamsungManager

Logger().create()
Sentry(
    settings.SENTRY_URL, settings.MODE, custom_log_formatter=sentry_remove_context
).create()


async def main():
    await SamsungManager().run()


if __name__ == "__main__":
    asyncio.run(main())
