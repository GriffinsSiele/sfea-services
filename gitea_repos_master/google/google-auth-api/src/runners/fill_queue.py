import asyncio
import json
import logging
import pathlib

from putils_logic.putils import PUtils
from rabbitmq_logic.publisher import RabbitMQPublisher

from src.config.settings import RABBITMQ_QUEUE, RABBITMQ_URL
from src.logger.logger import Logger
from src.runners.chosen_tasks import chosen_tasks
from src.runners.mobile_operators import get_random_mobile_phones

_current_file_path = pathlib.Path(__file__).parent.absolute()
_root_dir = PUtils.bp(_current_file_path, "..", "..")


async def fill_queue() -> None:
    Logger().create()
    tasks = get_random_mobile_phones(10)
    if chosen_tasks:
        tasks += chosen_tasks
    rabbit = await RabbitMQPublisher(RABBITMQ_URL, RABBITMQ_QUEUE).connect()
    for task in tasks:
        await rabbit.add_task(task)
        await asyncio.sleep(1)
    logging.info(f"Load {len(tasks)} task(s).")


async def send_one_task(task: str) -> None:
    rabbit = await RabbitMQPublisher(RABBITMQ_URL, RABBITMQ_QUEUE).connect()
    await rabbit.add_task(task, content_type="application/json")
    await asyncio.sleep(3)
    logging.info(f"Load {task} task.")


if __name__ == "__main__":
    asyncio.run(
        send_one_task(
            json.dumps(
                {
                    # "email": "",
                    "phone": "",
                }
            )
        )
    )
    # asyncio.run(fill_queue())
