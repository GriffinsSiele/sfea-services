import asyncio

from worker_classes.logger import Logger

from src.logic.schedule.schedule import ScheduleTasks


async def main():
    Logger().create()
    await asyncio.gather(ScheduleTasks().start())


asyncio.run(main())
