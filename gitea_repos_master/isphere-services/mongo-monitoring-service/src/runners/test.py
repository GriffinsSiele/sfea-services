import asyncio

from worker_classes.logger import Logger

from src.logic.actions.statistics import StatisticsAction


async def main():
    Logger().create()

    action = StatisticsAction()
    await action.call()


asyncio.run(main())
