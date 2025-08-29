import asyncio
import json
import logging

from rabbitmq_logic.publisher import RabbitMQPublisher

from src.config.settings import RABBITMQ_QUEUE_NAME, RABBITMQ_URL


async def send_one_task(task: str) -> None:
    rabbit = await RabbitMQPublisher(RABBITMQ_URL, RABBITMQ_QUEUE_NAME).connect()
    await rabbit.add_task(task, content_type="application/json")
    await asyncio.sleep(3)
    logging.info(f"Load {task} task.")


if __name__ == "__main__":
    asyncio.run(
        send_one_task(
            json.dumps(
                {
                    "email": "86am666@gmail.com",
                    "first_name": "АННА",
                    "last_name": "ХОЛИНА",
                    "starttime": 1711462670,
                    "timeout": 1000000,
                }
            )
        )
    )
