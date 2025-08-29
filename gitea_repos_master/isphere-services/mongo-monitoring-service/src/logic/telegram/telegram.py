import logging

import aiohttp

from src.config.settings import MODE


class TelegramAPI:
    def __init__(self, token, chat_id, logger=logging):
        self.token = token
        self.chat_id = chat_id
        self.logging = logger

    async def send_many(self, messages):
        if not messages:
            return
        return await self.send(
            "\n--------------------------------------------------\n".join(messages)
        )

    async def send(self, message):
        if not message:
            return

        message_for_log = message.replace("\n", "|")
        self.logging.info(f"Sending message: {message_for_log}")

        if not self.token:
            self.logging.info("No telegram token. Skip sending message.")
            return

        if MODE != "prod":
            self.logging.info(f"Skip message sending. {message}")
            return

        query = {"chat_id": self.chat_id, "text": message, "parseMode": "Markdown"}
        url = f"https://api.telegram.org/bot{self.token}/sendMessage"

        response = await self._send(method="GET", url=url, params=query)
        self.logging.info(f"Response telegram: {response}")

    async def _send(self, *args, **kwargs):
        async with aiohttp.ClientSession() as session:
            async with session.request(*args, **kwargs, timeout=5) as resp:
                return await resp.text()
