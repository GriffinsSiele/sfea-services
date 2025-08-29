import asyncio
import json
import logging
import pathlib

from putils_logic.putils import PUtils
from rabbitmq_logic.publisher import RabbitMQPublisher
from worker_classes.logger import Logger

from src.config.settings import RABBITMQ_QUEUE_HUAWEI, RABBITMQ_URL
from src.runners.chosen_tasks import chosen_tasks
from src.runners.mobile_operators import get_random_mobile_phones

_current_file_path = pathlib.Path(__file__).parent.absolute()
_root_dir = PUtils.bp(_current_file_path, "..", "..")


async def fill_queue() -> None:
    Logger().create()
    tasks = get_random_mobile_phones(1)
    if chosen_tasks:
        tasks += chosen_tasks
    rabbit = await RabbitMQPublisher(RABBITMQ_URL, RABBITMQ_QUEUE_HUAWEI).connect()
    for task in tasks:
        if task.isdigit():
            payload = {"phone": task}
        else:
            payload = {"email": task}
        await rabbit.add_task(
            json.dumps(payload),
            content_type="application/json",
        )
        await asyncio.sleep(20)
    logging.info(f"Load {len(tasks)} task(s).")


if __name__ == "__main__":
    asyncio.run(fill_queue())
