import logging
from asyncio import sleep

import aioschedule as schedule

from src.config.app import ConfigApp
from src.logic.actions.critical_min import CriticalMinWatcher
from src.logic.actions.inactive import InactiveWatcher
from src.logic.actions.migrate import MigrateAction
from src.logic.actions.statistics import StatisticsAction
from src.logic.actions.temp_locked import TempLockedWatcher
from src.logic.actions.underperforming_success import UnderperformingSuccessAction


class ScheduleTasks:
    async def start(self):
        logging.info("Started cron tasks")

        commands = {
            "CRITICAL_MIN": CriticalMinWatcher(),
            "MIGRATION": MigrateAction(),
            "INACTIVE": InactiveWatcher(),
            "TEMP_LOCKED": TempLockedWatcher(),
            "STATISTICS": StatisticsAction(),
            "UNDERPERFORMING_SUCCESS": UnderperformingSuccessAction(),
        }

        tasks_count = self.parse_commands(commands, schedule)
        logging.info(f"Created {tasks_count} cron task(s)")

        while True:
            await schedule.run_pending()
            await sleep(0.1)

    def parse_commands(self, commands, schedule):
        tasks = 0
        for app_name, interval in ConfigApp.TASKS.items():
            if app_name not in commands:
                continue

            action = commands[app_name].call
            schedule_action = lambda x: x

            if "min" in interval:
                seconds = float(interval.split(" ")[0])
                schedule_action = schedule.every(seconds).minutes.do
                tasks += 1

            if "at" in interval:
                time = interval.split(" ")[1]
                schedule_action = schedule.every().day.at(time).do
                tasks += 1

            schedule_action(action)
        return tasks
