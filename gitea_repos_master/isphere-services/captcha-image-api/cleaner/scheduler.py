import asyncio
from typing import Callable

from apscheduler.schedulers.asyncio import AsyncIOScheduler
from apscheduler.triggers.cron import CronTrigger
from livenessprobe_logic import HealthCheck

from src.common.utils import SingletonLogging
from src.config.cron_config import cron_settings


class CronJobScheduler(SingletonLogging):
    def __init__(self) -> None:
        super().__init__()
        self.scheduler = AsyncIOScheduler()

    async def run_job(self, job: Callable) -> None:
        self.scheduler.add_job(
            job,
            trigger=CronTrigger.from_crontab(cron_settings.DB_CLEANUP_CRON_RULE),
        )
        self.scheduler.start()
        while True:
            await asyncio.sleep(60)
            HealthCheck().checkpoint()
