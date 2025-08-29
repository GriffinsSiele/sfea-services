import datetime
import logging
from typing import Optional

import aio_pika
from aio_pika.abc import AbstractRobustConnection

from rabbitmq_logic.config import MessageType, RabbitMQConfig


class RabbitMQPublisher:
    def __init__(self, ampq_url: str, queue_name: str):
        """
        The __init__ function is called when an instance of the class is created.
        It initializes the attributes of the class, and it can take arguments that
        are passed through to __init__ as variables. In this case, we are setting up a
        connection to RabbitMQ using pika's BlockingConnection function.

        :param self: Refer to the object itself
        :param ampq_url:str: Specify the ampq url to connect to
        :param queue_name:str: Specify the name of the queue that will be created
        :return: The object itself
        :doc-author: Trelent
        """
        self.ampq_url = ampq_url
        self.queue_name = queue_name

        self.connection = None

    async def connect(self):
        """
        The connect function establishes a connection to the AMPQ server.
        It is called by the __init__ function of the class, and it returns an instance of
        the AbstractRobustConnection class from aio_pika.

        :param self: Refer to the object itself
        :return: A connection object
        :doc-author: Trelent
        """
        self.connection: AbstractRobustConnection = await aio_pika.connect_robust(
            self.ampq_url
        )

    async def close(self):
        """
        The close function closes the connection to the rabbitmq.

        :param self: Access the attributes and methods in the parent class
        :return: The await of the connection
        :doc-author: Trelent
        """
        return await self.connection.close()

    async def add_task(
        self,
        task: str,
        content_type: Optional[str] = "text/plain",
        priority: Optional[int] = None,
        expiration: Optional[int] = 30,
        type: Optional[MessageType.INNER_MESSAGE] = MessageType.INNER_MESSAGE,
    ):
        """
        The add_task function adds a task to the queue.

        :param self: Access the class instance
        :param task:str: Pass the body that will be processed
        :param content_type:Optional[str]=text/plain Specify the content type of the message
            Variants: text/plain, application/json
        :param priority:Optional[int]=None: Set the priority of the message
        :param expiration:Optional[int]=30: Set the expiration time of a message in sec
        :param type:Optional[MessageType.INNER_MESSAGE]=MessageType.INNER_MESSAGE: Specify the type of message
        :return: A future object
        :doc-author: Trelent
        """
        if type and not priority and type in RabbitMQConfig.message_type_to_priority:
            priority = RabbitMQConfig.message_type_to_priority[type]

        channel = await self.connection.channel()

        message = aio_pika.Message(
            body=task.encode(),
            content_type=content_type,
            priority=priority,
            expiration=expiration,
            timestamp=datetime.datetime.now(),
            type=type,
        )
        logging.info(f"Created message: {message}")

        await channel.default_exchange.publish(
            message,
            routing_key=self.queue_name,
        )
