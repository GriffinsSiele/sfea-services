import asyncio
import aioschedule as schedule
import threading
import time

from .proxy_poll import ProxyPoll
from .statistic_cleaner import StatisticCleaner


class TaskRunner:
    @staticmethod
    def main_thread():
        schedule.every(1).minutes.do(ProxyPoll.poll)
        schedule.every().day.at("00:00").do(StatisticCleaner.clean_statistic)
        loop = asyncio.new_event_loop()
        asyncio.set_event_loop(loop)
        while 1:
            loop.run_until_complete(schedule.run_pending())
            time.sleep(1)

    @staticmethod
    def run_thread():
        worker_thread = threading.Thread(
            target=TaskRunner.main_thread, daemon=True, name="cron-tasks"
        )
        worker_thread.start()
