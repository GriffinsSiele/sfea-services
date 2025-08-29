import asyncio
import json
import logging
from typing import Callable

import aio_pika
from aio_pika.abc import (
    AbstractRobustConnection,
    AbstractRobustChannel,
    AbstractRobustQueue,
    AbstractIncomingMessage,
)

from rabbitmq_logic.config import RabbitMQConfig


class RabbitMQConsumer:
    def __init__(
        self,
        ampq_url: str,
        queue_name: str,
        on_message_callback: Callable,
        consumer_count=1,
    ):
        """
        The __init__ function is called when an instance of the class is created.
        It initializes all the variables that are unique to each instance, such as
        the ampq_url and queue_name. It also sets up a connection to RabbitMQ, creates
        a channel on that connection, and declares a queue for us to receive messages
        on.

        :param self: Reference the object instance of the class
        :param ampq_url:str: Specify the ampq url to connect to
        :param queue_name:str: Identify the queue
        :param on_message_callback:Callable: Specify the function to be called when a message is received
        :param consumer_count=1: Specify the number of consumers that will be created for this queue
        :param : Define the rabbitmq url
        :return: The object that is created
        :doc-author: Trelent
        """
        self.ampq_url = ampq_url
        self.queue_name = queue_name
        self.consumer_count = consumer_count
        self.on_message_callback = on_message_callback

        self.connection, self.channel, self.queue = None, None, None

    async def connect(self):
        """
        The connect function is used to connect to the RabbitMQ server.
        It creates a connection object and a channel object, which are then assigned
        to self.connection and self.channel respectively.

        :param self: Access the class attributes and methods
        :return: The connection object
        :doc-author: Trelent
        """
        self.connection: AbstractRobustConnection = await aio_pika.connect_robust(
            self.ampq_url
        )

        # Creating channel
        self.channel: AbstractRobustChannel = await self.connection.channel()

        # Maximum message count which will be processing at the same time.
        await self.channel.set_qos(prefetch_count=self.consumer_count)

        # Declaring queue
        self.queue: AbstractRobustQueue = await self.channel.declare_queue(
            self.queue_name,
            auto_delete=False,
            durable=True,
            arguments={"x-max-priority": RabbitMQConfig.MAX_PRIORITY_VALUE},
        )

    async def close(self):
        """
        The close function closes the connection to the rabbitmq.

        :param self: Access the attributes and methods in the parent class
        :return: The await of the connection
        :doc-author: Trelent
        """
        return await self.connection.close()

    async def run(self):
        """
        The run function is the main entry point for the application. It creates a connection to RabbitMQ,
        and then starts an asynchronous consumer that waits for messages from RabbitMQ. The run function also
        creates a queue and passes it to the consumer so that it can receive messages from RabbitMQ.

        :param self: Access the class attributes
        :return: The awaitable object of the queue
        :doc-author: Trelent
        """
        await self.queue.consume(self.__on_message_callback, no_ack=False)
        try:
            # Wait until terminate
            await asyncio.Future()
        finally:
            await self.connection.close()

    async def __on_message_callback(self, message: AbstractIncomingMessage):
        """
        The __on_message_callback function is a private function that handles the actual message processing.
        It takes in an AbstractIncomingMessage object and passes it to the on_message_callback function, which
        is defined by the user. The on_message callback should return a tuple of two values: (ack, args).
        The first value is either &quot;ack&quot; or &quot;nack&quot;, indicating whether or not to send an acknowledgement back
        to the sender. The second value is a dictionary of keyword arguments that will be passed into whatever
        method was called for acknowledgement (e.g., if you call message.nack(reason=&quot;I

        :param self: Access the class attributes
        :param message:AbstractIncomingMessage: Pass the message that was received by the bot
        :return: A tuple, containing a string and a dictionary
        :doc-author: Trelent
        """
        logging.info(f"Getting message: {message.body}")

        message = self.__message_preprocess(message)
        response = await self.on_message_callback(message)

        ack, args = self.__validate_response(response)
        logging.info(f"Message status: {ack}. Args: {args}")

        await getattr(message, ack)(**args)

    def __validate_response(self, response: dict):
        """
        The __validate_response function is a helper function that validates the response from the server.
        It checks to see if there is a response, and then checks to see if it contains an &quot;ack&quot; key. If not, it sets
        the value of &quot;ack&quot; in the dictionary to be &quot;nak&quot;. It also returns any other arguments contained in the dictionary.

        :param self: Reference the class instance
        :param response:dict: Validate the response from the server
        :return: A tuple of the ack and args
        :doc-author: Trelent
        """
        allowed = ["ack", "reject", "nack"]
        if not response or "ack" not in response or response.get("ack") not in allowed:
            response = {"ack": "ack"}

        ack, args = response.pop("ack"), response
        return ack, args

    def __message_preprocess(self, message: AbstractIncomingMessage):
        """
        The __message_preprocess function is a private method that takes an incoming message and
        attempts to decode the body of the message into a JSON object. If this fails, it will attempt
        to decode the body as text. This function is called by __message_preprocess in order to ensure
        that all messages are decoded consistently.

        :param self: Access the class attributes and methods
        :param message:AbstractIncomingMessage: Get the message body and content type
        :return: A message object with the body decoded from bytes to a string
        :doc-author: Trelent
        """
        content_type = message.content_type or ""
        if "application/json" in content_type:
            message.body = json.loads(message.body)
        elif "text/plain" in content_type or not content_type:
            message.body = message.body.decode()
        return message
