import asyncio

from rabbitmq_logic.publisher import RabbitMQPublisher

from src.config.settings import RABBITMQ_QUEUE, RABBITMQ_URL


async def run():
    rabbitmq = await RabbitMQPublisher(RABBITMQ_URL, RABBITMQ_QUEUE).connect()

    for i in range(100, 130):
        await rabbitmq.add_task(f"79208316{i}")


if __name__ == "__main__":
    asyncio.run(run())
