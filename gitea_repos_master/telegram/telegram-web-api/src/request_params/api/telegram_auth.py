from telethon import TelegramClient
from telethon.sessions import StringSession


class TelegramAuth:
    def __init__(self, auth_key, api_id=None, api_hash=None, password=None, proxy=None):
        self.auth_key = auth_key
        self.api_id = api_id
        self.api_hash = api_hash
        self.proxy = proxy

    async def request(self):
        client = TelegramClient(
            StringSession(self.auth_key),
            api_id=self.api_id,
            api_hash=self.api_hash,
            proxy=self.proxy,
            connection_retries=3,
            request_retries=2,
            retry_delay=2,
            sequential_updates=True,
            receive_updates=False,
            timeout=5,
        )
        await client.connect()
        return client
