import asyncio
import time

from livenessprobe_logic import HealthCheck
from worker_classes.thread.timing import timing

from src.config.settings import (
    CHECK_SESSION_INTERVAL_AUTH,
    CHECK_SESSION_INTERVAL_NAME_PERSON,
)
from src.logger.context_logger import logging
from src.logger.logger_adapter import task_context_var
from src.logic.samsung.session_manager_auth import SamsungSearchManagerAuth
from src.logic.samsung.session_manager_common import SamsungSearchManagerCommon
from src.logic.samsung.session_manager_name import SamsungSearchManagerName
from src.utils import ExtStr


class SamsungManager:
    """Управляет обработчиками auth и person"""

    manager_auth = SamsungSearchManagerAuth()
    manager_name_person = SamsungSearchManagerName()

    async def run(self):
        """Запускает обработчики, которые управляют сессиями"""
        start_next_time_auth = self.count_start_time(CHECK_SESSION_INTERVAL_AUTH)
        start_next_time_person = self.count_start_time(CHECK_SESSION_INTERVAL_NAME_PERSON)
        await self.run_manager(self.manager_auth, "Auth")
        await self.run_manager(self.manager_name_person, "Person")
        health_check = HealthCheck()

        while True:
            if time.time() > start_next_time_auth:
                start_next_time_auth = self.count_start_time(CHECK_SESSION_INTERVAL_AUTH)
                await self.run_manager(self.manager_auth, "Auth")
                health_check.checkpoint()

            if time.time() > start_next_time_person:
                start_next_time_person = self.count_start_time(
                    CHECK_SESSION_INTERVAL_NAME_PERSON
                )
                await self.run_manager(self.manager_name_person, "Person")
                health_check.checkpoint()

            await self.sleeper([start_next_time_auth, start_next_time_person])

    async def run_manager(self, manager: SamsungSearchManagerCommon, name: str) -> None:
        """Запускает на выполнение обработчик.

        :param manager: Обработчик для запуска.
        :param name: Кодовое имя обработчика для контекста логера.
        :return: None
        """
        task_context_var.set(name)
        try:
            await self.process_manager(manager)
        except Exception as e:
            logging.warning(f"{name} managed failed with: {ExtStr(e).short()}")

    @staticmethod
    @timing("Total processing task time")
    async def process_manager(manager: SamsungSearchManagerCommon) -> None:
        """Обертка для применения декоратора timing - логирует время выполнения функции.
        В данном применении логирует время работы обработчика.
        """
        await manager.run()
        await manager.stop()

    @staticmethod
    def count_start_time(interval: int) -> float:
        """Рассчитывает время, когда должен запуститься обработчик.

        :param interval: Интервал запуска обработчика.
        :return: None
        """
        return time.time() + interval

    @staticmethod
    async def sleeper(times: list) -> None:
        """Рассчитывает время до запуска следующего ближайшего обработчика
        и засыпает на это время.

        :param times: Список времен, когда должны запуститься обработчики.
        :return: None
        """
        task_context_var.set("")
        current_time = time.time()
        delta_time = []
        for tm in times:
            if tm < current_time:
                logging.info("No time to sleep")
                return None
            delta_time.append(tm - current_time)
        sleep_time = min(delta_time)
        logging.info("Sleeping time {:.3f} sec ...".format(sleep_time))
        await asyncio.sleep(sleep_time)
